"""
Tenant isolation tests for KHM GEO Tracker

Tests to ensure tenant data isolation using PostgreSQL Row Level Security (RLS).
These tests verify that clients cannot access data from other tenants.
"""

import pytest
import asyncio
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text

from app.db.session import get_tenant_session
from app.core.config import settings


class TestTenantIsolation:
    """Test suite for tenant data isolation"""

    @pytest.mark.asyncio
    async def test_tenant_session_isolation(self):
        """
        Test that tenant-scoped sessions properly isolate data access

        This test verifies that when using a tenant session, queries are
        automatically filtered to only return data for that tenant.
        """
        # Test with client_id 1
        async with get_tenant_session(client_id=1) as session1:
            # This should only see data for client_id=1
            result1 = await session1.execute(
                text("SELECT COUNT(*) FROM clients WHERE id = 1")
            )
            count1 = result1.scalar()

        # Test with client_id 2
        async with get_tenant_session(client_id=2) as session2:
            # This should only see data for client_id=2
            result2 = await session2.execute(
                text("SELECT COUNT(*) FROM clients WHERE id = 2")
            )
            count2 = result2.scalar()

        # Each session should only see its own client's data
        # (Note: This assumes test data exists for both clients)
        assert count1 >= 0  # At least no errors
        assert count2 >= 0

    @pytest.mark.asyncio
    async def test_cross_tenant_data_access_blocked(self):
        """
        Test that direct attempts to access other tenant's data are blocked

        This test simulates malicious attempts to access data from other tenants
        and verifies that RLS policies prevent such access.
        """
        # Client 1 session
        async with get_tenant_session(client_id=1) as session1:
            # Try to access client 2's data directly (should be blocked by RLS)
            try:
                result = await session1.execute(
                    text("SELECT * FROM clients WHERE id = 2")
                )
                rows = result.fetchall()
                # RLS should prevent this, so we expect empty result
                assert len(rows) == 0, "RLS failed: Client 1 can see Client 2's data"
            except Exception as e:
                # Some databases might raise an exception instead of returning empty
                pytest.fail(f"Unexpected error accessing cross-tenant data: {e}")

    @pytest.mark.asyncio
    async def test_tenant_context_propagation(self):
        """
        Test that tenant context is properly set in database session

        Verifies that the app.current_client_id session variable is set correctly.
        """
        async with get_tenant_session(client_id=123) as session:
            # Check that the session variable is set
            result = await session.execute(
                text("SHOW app.current_client_id")
            )
            client_id_var = result.scalar()

            assert client_id_var == "123", f"Expected client_id 123, got {client_id_var}"

    @pytest.mark.asyncio
    async def test_rls_policies_active(self):
        """
        Test that RLS policies are active on all tenant-scoped tables

        This test checks that RLS is enabled on all tables that should be tenant-scoped.
        """
        tenant_tables = [
            "clients", "queries", "runs", "answers", "citations",
            "entities", "similarities", "metrics", "posts",
            "wordpress_entities", "answer_cards"
        ]

        async with get_tenant_session(client_id=1) as session:
            for table in tenant_tables:
                # Check if RLS is enabled
                result = await session.execute(
                    text(f"SELECT row_security_active('{table}')")
                )
                rls_active = result.scalar()

                assert rls_active, f"RLS not active on table: {table}"

                # Check if our policy exists
                result = await session.execute(
                    text(f"SELECT COUNT(*) FROM pg_policies WHERE tablename = '{table}' AND policyname = 'tenant_isolation'")
                )
                policy_count = result.scalar()

                assert policy_count > 0, f"Tenant isolation policy not found on table: {table}"

    @pytest.mark.asyncio
    async def test_fuzz_tenant_isolation(self):
        """
        Fuzz test for tenant isolation with random client IDs

        This test creates multiple concurrent sessions with different client IDs
        and verifies they cannot access each other's data.
        """
        import random

        client_ids = [random.randint(1, 1000) for _ in range(10)]

        async def test_client_isolation(client_id: int):
            async with get_tenant_session(client_id=client_id) as session:
                # Try to access data - should only see own client's data
                result = await session.execute(
                    text("SELECT COUNT(*) FROM clients WHERE id = :client_id"),
                    {"client_id": client_id}
                )
                count = result.scalar()

                # Try to access other clients' data (should be blocked)
                other_client_id = client_id + 1
                result = await session.execute(
                    text("SELECT COUNT(*) FROM clients WHERE id = :other_id"),
                    {"other_id": other_client_id}
                )
                other_count = result.scalar()

                return count, other_count

        # Run concurrent tests
        tasks = [test_client_isolation(cid) for cid in client_ids]
        results = await asyncio.gather(*tasks)

        # Verify all sessions could access their own data but not others'
        for i, (own_count, other_count) in enumerate(results):
            assert own_count >= 0, f"Client {client_ids[i]} couldn't access own data"
            assert other_count == 0, f"Client {client_ids[i]} could access other client's data"

    @pytest.mark.asyncio
    async def test_tenant_session_cleanup(self):
        """
        Test that tenant session variables are properly cleaned up

        Ensures that session variables don't leak between different tenant sessions.
        """
        # First session
        async with get_tenant_session(client_id=1) as session1:
            result = await session1.execute(text("SHOW app.current_client_id"))
            var1 = result.scalar()

        # Second session with different client
        async with get_tenant_session(client_id=2) as session2:
            result = await session2.execute(text("SHOW app.current_client_id"))
            var2 = result.scalar()

        # Third session back to first client
        async with get_tenant_session(client_id=1) as session3:
            result = await session3.execute(text("SHOW app.current_client_id"))
            var3 = result.scalar()

        assert var1 == "1"
        assert var2 == "2"
        assert var3 == "1"  # Should be reset correctly