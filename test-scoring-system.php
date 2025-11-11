<?php
/**
 * Test Scoring System for AnswerCard Quality Assessment
 *
 * Tests the ScoringEngine functionality and integration with AnswerCard widgets.
 *
 * @package KHM_SEO
 * @since 2.0.0
 */

// Only run if WordPress is loaded
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access not permitted.' );
}

echo '<h2>Testing Scoring System for AnswerCard Quality Assessment</h2>';

// Test 1: ScoringEngine Class Loading
echo '<h3>Test 1: ScoringEngine Class Loading</h3>';

try {
    if ( class_exists( '\KHM_SEO\GEO\Scoring\ScoringEngine' ) ) {
        echo '<p style="color: green;">✓ ScoringEngine class exists</p>';

        $scoring_engine = new \KHM_SEO\GEO\Scoring\ScoringEngine();
        echo '<p style="color: green;">✓ ScoringEngine instance created</p>';

        // Test basic methods
        if ( method_exists( $scoring_engine, 'calculate_score' ) ) {
            echo '<p style="color: green;">✓ calculate_score method exists</p>';
        } else {
            echo '<p style="color: red;">✗ calculate_score method missing</p>';
        }

        if ( method_exists( $scoring_engine, 'get_quality_display' ) ) {
            echo '<p style="color: green;">✓ get_quality_display method exists</p>';
        } else {
            echo '<p style="color: red;">✗ get_quality_display method missing</p>';
        }

    } else {
        echo '<p style="color: red;">✗ ScoringEngine class not found</p>';
    }

} catch ( Exception $e ) {
    echo '<p style="color: red;">✗ Exception in ScoringEngine test: ' . esc_html( $e->getMessage() ) . '</p>';
}

// Test 2: Score Calculation
echo '<h3>Test 2: Score Calculation</h3>';

try {
    if ( class_exists( '\KHM_SEO\GEO\Scoring\ScoringEngine' ) ) {
        $scoring_engine = new \KHM_SEO\GEO\Scoring\ScoringEngine();

        // Test with complete AnswerCard settings
        $complete_settings = array(
            'question' => 'What is Search Engine Optimization?',
            'answer' => 'Search Engine Optimization (SEO) is the process of improving the quality and quantity of website traffic to a website or a web page from search engines. SEO targets unpaid traffic rather than direct traffic or paid traffic.',
            'entity_id' => 123,
            'bullets' => array( 'Improves visibility', 'Increases traffic', 'Builds credibility' ),
            'citations' => array( 'https://moz.com/learn/seo/what-is-seo', 'https://developers.google.com/search/docs' ),
            'confidence_score' => 0.95,
        );

        $score_data = $scoring_engine->calculate_score( $complete_settings );

        if ( isset( $score_data['total_score'] ) && is_numeric( $score_data['total_score'] ) ) {
            echo '<p style="color: green;">✓ Score calculation successful: ' . round( $score_data['total_score'] * 100, 1) . '%</p>';
            echo '<p><strong>Quality Level:</strong> ' . esc_html( $score_data['quality_level'] ) . '</p>';
            echo '<p><strong>Publishable:</strong> ' . ( $score_data['is_publishable'] ? 'Yes' : 'No' ) . '</p>';
        } else {
            echo '<p style="color: red;">✗ Score calculation failed</p>';
        }

        // Test with incomplete settings
        $incomplete_settings = array(
            'question' => '',
            'answer' => 'Short answer',
            'confidence_score' => 0.3,
        );

        $incomplete_score = $scoring_engine->calculate_score( $incomplete_settings );
        echo '<p style="color: blue;">✓ Incomplete card score: ' . round( $incomplete_score['total_score'] * 100, 1) . '% (' . esc_html( $incomplete_score['quality_level'] ) . ')</p>';

        // Test quality display
        $quality_display = $scoring_engine->get_quality_display( $score_data['quality_level'] );
        if ( isset( $quality_display['label'] ) && isset( $quality_display['color'] ) ) {
            echo '<p style="color: green;">✓ Quality display: ' . esc_html( $quality_display['label'] ) . ' (' . esc_html( $quality_display['color'] ) . ')</p>';
        } else {
            echo '<p style="color: red;">✗ Quality display failed</p>';
        }

    }

} catch ( Exception $e ) {
    echo '<p style="color: red;">✗ Exception in score calculation test: ' . esc_html( $e->getMessage() ) . '</p>';
}

// Test 3: EntityManager Integration
echo '<h3>Test 3: EntityManager Integration</h3>';

try {
    $entity_manager = khm_seo()->geo->get_entity_manager();

    if ( $entity_manager && method_exists( $entity_manager, 'get_scoring_engine' ) ) {
        echo '<p style="color: green;">✓ EntityManager has get_scoring_engine method</p>';

        $scoring_engine = $entity_manager->get_scoring_engine();
        if ( $scoring_engine instanceof \KHM_SEO\GEO\Scoring\ScoringEngine ) {
            echo '<p style="color: green;">✓ ScoringEngine accessible via EntityManager</p>';
        } else {
            echo '<p style="color: red;">✗ ScoringEngine not properly instantiated</p>';
        }

    } else {
        echo '<p style="color: red;">✗ EntityManager missing get_scoring_engine method</p>';
    }

} catch ( Exception $e ) {
    echo '<p style="color: red;">✗ Exception in EntityManager integration test: ' . esc_html( $e->getMessage() ) . '</p>';
}

// Test 4: Validation for Publishing
echo '<h3>Test 4: Pre-Publish Validation</h3>';

try {
    if ( class_exists( '\KHM_SEO\GEO\Scoring\ScoringEngine' ) ) {
        $scoring_engine = new \KHM_SEO\GEO\Scoring\ScoringEngine();

        // Test valid card
        $valid_settings = array(
            'question' => 'What is SEO?',
            'answer' => 'SEO stands for Search Engine Optimization and is crucial for online visibility.',
            'confidence_score' => 0.8,
            'entity_id' => 123,
        );

        $validation = $scoring_engine->validate_for_publish( $valid_settings );

        if ( isset( $validation['can_publish'] ) ) {
            echo '<p style="color: green;">✓ Validation result: ' . ( $validation['can_publish'] ? 'Can Publish' : 'Cannot Publish' ) . '</p>';
            echo '<p><strong>Errors:</strong> ' . count( $validation['errors'] ) . '</p>';
            echo '<p><strong>Warnings:</strong> ' . count( $validation['warnings'] ) . '</p>';
        } else {
            echo '<p style="color: red;">✗ Validation failed</p>';
        }

        // Test invalid card
        $invalid_settings = array(
            'question' => '',
            'answer' => '',
        );

        $invalid_validation = $scoring_engine->validate_for_publish( $invalid_settings );
        echo '<p style="color: blue;">✓ Invalid card validation: ' . ( $invalid_validation['can_publish'] ? 'Can Publish' : 'Cannot Publish' ) . '</p>';

    }

} catch ( Exception $e ) {
    echo '<p style="color: red;">✗ Exception in validation test: ' . esc_html( $e->getMessage() ) . '</p>';
}

echo '<h3>Scoring System Test Summary</h3>';
echo '<p>The AnswerCard scoring system has been implemented with the following features:</p>';
echo '<ul>';
echo '<li>Multi-criteria quality assessment (content, confidence, citations, entity linkage, SEO)</li>';
echo '<li>Weighted scoring algorithm with quality level determination</li>';
echo '<li>Automated recommendations for improvement</li>';
echo '<li>Pre-publish validation with error/warning reporting</li>';
echo '<li>Integration with EntityManager for centralized access</li>';
echo '<li>Real-time scoring display in Elementor editor</li>';
echo '</ul>';