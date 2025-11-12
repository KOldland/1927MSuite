# 🚀 GEO Tracker Deployment Guide

**Date:** November 12, 2025  
**Version:** 1.0.0  
**Status:** Production Ready  

---

## 📋 Deployment Overview

This guide covers the complete deployment of the **headless GEO Tracker + WordPress connector** system.

### 🏗️ Architecture Components

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   WordPress     │    │   GEO Tracker   │    │   AI Services   │
│   Sites (N)     │◄──►│   FastAPI        │◄──►│ OpenAI, Brave   │
│                 │    │   Backend        │    │ Perplexity, etc │
│ • KHM SEO Plugin│    │                 │    │                 │
│ • Tracker Conn. │    │ • PostgreSQL     │    └─────────────────┘
│ • JWT Auth      │    │ • Redis Cache    │
└─────────────────┘    │ • Celery Workers │
                       └─────────────────┘
```

---

## 🖥️ Infrastructure Requirements

### **GEO Tracker Backend Server**

| Component | Specification | Purpose |
|-----------|---------------|---------|
| **OS** | Ubuntu 22.04 LTS | Stable Linux distribution |
| **CPU** | 4+ cores | Handle concurrent AI API calls |
| **RAM** | 8GB+ | Memory for embeddings, caching |
| **Storage** | 100GB+ SSD | Database, logs, cached data |
| **Network** | 1Gbps | High bandwidth for AI APIs |

### **WordPress Sites**

| Component | Specification | Purpose |
|-----------|---------------|---------|
| **OS** | Ubuntu 22.04 LTS | WordPress compatibility |
| **CPU** | 2+ cores | Handle WordPress + plugin |
| **RAM** | 4GB+ | WordPress memory requirements |
| **Storage** | 50GB+ SSD | WordPress files, database |
| **PHP** | 8.1+ | Plugin compatibility |

### **Database Server** (can be same as backend)

| Component | Specification | Purpose |
|-----------|---------------|---------|
| **PostgreSQL** | 15+ | Primary database |
| **PostGIS** | Latest | Spatial data support |
| **Storage** | 200GB+ SSD | Data retention (180d raw, 365d KPIs) |

---

## 📦 Software Dependencies

### **GEO Tracker Backend**

```bash
# Python & System Packages
python3.11
pip
postgresql-client
redis-server
nginx
certbot
supervisor
git

# Python Packages (requirements.txt)
fastapi==0.104.1
uvicorn[standard]==0.24.0
sqlalchemy==2.0.23
alembic==1.12.1
psycopg2-binary==2.9.9
redis==5.0.1
celery==5.3.4
pydantic==2.5.0
python-jose[cryptography]==3.3.0
openai==1.3.7
requests==2.31.0
```

### **WordPress Sites**

```bash
# System Packages
apache2/nginx
php8.1-fpm
php8.1-mysql
php8.1-curl
php8.1-gd
php8.1-mbstring
php8.1-xml
php8.1-zip
mysql-server/mariadb
certbot
git
```

---

## 🔧 Pre-Deployment Checklist

### **1. Domain & DNS Configuration**

```bash
# GEO Tracker Domain
tracker.yourdomain.com → Backend Server IP

# WordPress Sites (examples)
site1.yourdomain.com → WordPress Server 1 IP
site2.yourdomain.com → WordPress Server 2 IP
```

### **2. SSL Certificates**

```bash
# Let's Encrypt for all domains
certbot --nginx -d tracker.yourdomain.com
certbot --apache -d site1.yourdomain.com
certbot --apache -d site2.yourdomain.com
```

### **3. Firewall Configuration**

```bash
# GEO Tracker Server
ufw allow 22/tcp      # SSH
ufw allow 80/tcp      # HTTP
ufw allow 443/tcp     # HTTPS
ufw allow 8000/tcp    # FastAPI (behind nginx)

# WordPress Servers
ufw allow 22/tcp      # SSH
ufw allow 80/tcp      # HTTP
ufw allow 443/tcp     # HTTPS
```

---

## 🚀 GEO Tracker Backend Deployment

### **Step 1: Server Setup**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y python3.11 python3.11-venv postgresql-client redis-server nginx supervisor git

# Create application user
sudo useradd -m -s /bin/bash geotracker
sudo usermod -aG sudo geotracker

# Switch to application user
sudo su - geotracker
```

### **Step 2: Database Setup**

```bash
# Install PostgreSQL (on database server)
sudo apt install -y postgresql-15 postgresql-15-postgis-3

# Create database and user
sudo -u postgres psql
```

```sql
-- Create database and user
CREATE DATABASE geo_tracker;
CREATE USER geo_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE geo_tracker TO geo_user;

-- Enable PostGIS
\c geo_tracker;
CREATE EXTENSION postgis;

-- Exit PostgreSQL
\q
```

### **Step 3: Application Deployment**

```bash
# Clone repository
git clone https://github.com/KOldland/1927MSuite.git
cd 1927MSuite/geo-tracker

# Create virtual environment
python3.11 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Create environment file
cat > .env << EOF
# Database
DATABASE_URL=postgresql://geo_user:your_secure_password@localhost:5432/geo_tracker

# Redis
REDIS_URL=redis://localhost:6379/0

# JWT
JWT_SECRET_KEY=your-256-bit-secret-key-here
JWT_ALGORITHM=RS256
JWT_ACCESS_TOKEN_EXPIRE_MINUTES=15

# AI API Keys (secure these!)
OPENAI_API_KEY=sk-your-openai-key
PERPLEXITY_API_KEY=pplx-your-key
BRAVE_API_KEY=your-brave-key
BING_API_KEY=your-bing-key

# External Services
GOOGLE_ANALYTICS_CREDENTIALS_PATH=/path/to/ga4-credentials.json
GOOGLE_SEARCH_CONSOLE_CREDENTIALS_PATH=/path/to/gsc-credentials.json

# Application
APP_ENV=production
DEBUG=False
LOG_LEVEL=INFO

# CORS
ALLOWED_ORIGINS=["https://site1.yourdomain.com", "https://site2.yourdomain.com"]
EOF
```

### **Step 4: Database Migration**

```bash
# Run database migrations
alembic upgrade head

# Verify database
python -c "from app.db.session import get_db; next(get_db()).execute('SELECT 1')"
```

### **Step 5: Nginx Configuration**

```bash
# Create nginx site configuration
sudo cat > /etc/nginx/sites-available/geo-tracker << EOF
server {
    listen 80;
    server_name tracker.yourdomain.com;

    # Redirect HTTP to HTTPS
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tracker.yourdomain.com;

    # SSL configuration
    ssl_certificate /etc/letsencrypt/live/tracker.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tracker.yourdomain.com/privkey.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Proxy to FastAPI
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # Timeout settings
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
    }

    # Static files
    location /static/ {
        alias /home/geotracker/1927MSuite/geo-tracker/static/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/geo-tracker /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### **Step 6: Supervisor Configuration**

```bash
# Create supervisor configuration
sudo cat > /etc/supervisor/conf.d/geo-tracker.conf << EOF
[program:geo-tracker]
command=/home/geotracker/1927MSuite/geo-tracker/venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 4
directory=/home/geotracker/1927MSuite/geo-tracker
user=geotracker
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/geo-tracker.log
environment=PATH="/home/geotracker/1927MSuite/geo-tracker/venv/bin"

[program:geo-tracker-worker]
command=/home/geotracker/1927MSuite/geo-tracker/venv/bin/celery -A app.worker worker --loglevel=info
directory=/home/geotracker/1927MSuite/geo-tracker
user=geotracker
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/geo-tracker-worker.log
environment=PATH="/home/geotracker/1927MSuite/geo-tracker/venv/bin"
EOF

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start geo-tracker
sudo supervisorctl start geo-tracker-worker
```

---

## 🌐 WordPress Site Deployment

### **Step 1: Server Setup**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y apache2 php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip mariadb-server certbot python3-certbot-apache git

# Secure MySQL
sudo mysql_secure_installation
```

### **Step 2: WordPress Installation**

```bash
# Create database
sudo mysql -u root -p
```

```sql
CREATE DATABASE wordpress_site1;
CREATE USER 'wp_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON wordpress_site1.* TO 'wp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Download WordPress
cd /var/www
sudo wget https://wordpress.org/latest.tar.gz
sudo tar -xzf latest.tar.gz
sudo mv wordpress site1.yourdomain.com
sudo chown -R www-data:www-data site1.yourdomain.com

# Configure WordPress
cd site1.yourdomain.com
sudo cp wp-config-sample.php wp-config.php
sudo nano wp-config.php
```

```php
// Update database settings
define('DB_NAME', 'wordpress_site1');
define('DB_USER', 'wp_user');
define('DB_PASSWORD', 'secure_password');
define('DB_HOST', 'localhost');

// Security keys (generate at https://api.wordpress.org/secret-key/1.1/salt/)
define('AUTH_KEY', 'your-unique-key');
define('SECURE_AUTH_KEY', 'your-unique-key');
define('LOGGED_IN_KEY', 'your-unique-key');
define('NONCE_KEY', 'your-unique-key');
define('AUTH_SALT', 'your-unique-key');
define('SECURE_AUTH_SALT', 'your-unique-key');
define('LOGGED_IN_SALT', 'your-unique-key');
define('NONCE_SALT', 'your-unique-key');
```

### **Step 3: Apache Configuration**

```bash
# Create virtual host
sudo cat > /etc/apache2/sites-available/site1.yourdomain.com.conf << EOF
<VirtualHost *:80>
    ServerName site1.yourdomain.com
    DocumentRoot /var/www/site1.yourdomain.com

    <Directory /var/www/site1.yourdomain.com>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/site1_error.log
    CustomLog \${APACHE_LOG_DIR}/site1_access.log combined
</VirtualHost>
EOF

# Enable site
sudo a2ensite site1.yourdomain.com.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### **Step 4: SSL Configuration**

```bash
# Get SSL certificate
sudo certbot --apache -d site1.yourdomain.com
```

### **Step 5: KHM SEO Plugin Installation**

```bash
# Install plugin
cd /var/www/site1.yourdomain.com/wp-content/plugins
sudo git clone https://github.com/KOldland/1927MSuite.git khm-seo-temp
sudo cp -r khm-seo-temp/wp-content/plugins/khm-seo .
sudo rm -rf khm-seo-temp

# Set permissions
sudo chown -R www-data:www-data khm-seo
```

### **Step 6: WordPress Configuration**

1. **Access WordPress admin:** `https://site1.yourdomain.com/wp-admin`
2. **Install KHM SEO plugin** via admin interface
3. **Configure GEO Tracker connection:**
   - Go to **GEO Tracker** → **Connection** tab
   - Set **Tracker URL:** `https://tracker.yourdomain.com`
   - Generate **Client ID** (use site identifier)
   - Click **Generate RSA Keypair**
   - Click **Test Connection**

---

## 🔍 Testing & Validation

### **GEO Tracker Backend Tests**

```bash
# Health check
curl https://tracker.yourdomain.com/health

# JWKS endpoint
curl https://tracker.yourdomain.com/.well-known/jwks.json

# API documentation
curl https://tracker.yourdomain.com/docs
```

### **WordPress Integration Tests**

```bash
# Test connection from WordPress
# 1. Login to WordPress admin
# 2. Go to GEO Tracker → Connection
# 3. Click "Test Connection"
# 4. Verify success message

# Test dashboard embedding
# 1. Go to GEO Tracker → Dashboard tab
# 2. Verify iframe loads correctly
```

### **End-to-End Tests**

```bash
# Create test post in WordPress
# Verify it appears in Tracker dashboard
# Check AI visibility metrics update
```

---

## 📊 Monitoring & Maintenance

### **Log Files**

```bash
# GEO Tracker logs
/var/log/geo-tracker.log
/var/log/geo-tracker-worker.log

# WordPress logs
/var/log/apache2/site1_error.log
/var/log/apache2/site1_access.log
```

### **Backup Strategy**

```bash
# Database backup (daily)
pg_dump geo_tracker > geo_tracker_$(date +%Y%m%d).sql

# WordPress backup (daily)
tar -czf wordpress_backup_$(date +%Y%m%d).tar.gz /var/www/site1.yourdomain.com
```

### **Monitoring Setup**

```bash
# Install monitoring
sudo apt install -y prometheus prometheus-nginx-exporter

# Configure alerts for:
# - API response times > 5s
# - Error rates > 5%
# - Database connection issues
# - AI API quota exhaustion
```

---

## 🚨 Troubleshooting

### **Common Issues**

1. **Connection Failed**
   - Check firewall settings
   - Verify SSL certificates
   - Confirm DNS resolution

2. **JWT Authentication Errors**
   - Regenerate RSA keypair
   - Check system time sync
   - Verify JWKS endpoint accessibility

3. **Database Connection Issues**
   - Check PostgreSQL service status
   - Verify connection credentials
   - Test network connectivity

4. **AI API Failures**
   - Check API key validity
   - Monitor rate limits
   - Verify quota availability

---

## 📞 Support & Documentation

- **API Documentation:** `https://tracker.yourdomain.com/docs`
- **WordPress Plugin Docs:** Available in plugin admin
- **Logs:** Check application and system logs
- **Health Checks:** `/health` endpoint for system status

---

## ✅ Deployment Checklist

### **Pre-Deployment**
- [ ] Domain DNS configured
- [ ] SSL certificates obtained
- [ ] Firewall rules configured
- [ ] Server security hardened

### **GEO Tracker Backend**
- [ ] PostgreSQL database created
- [ ] Redis cache configured
- [ ] Application deployed
- [ ] Environment variables set
- [ ] Database migrations run
- [ ] Nginx configured
- [ ] Supervisor processes running
- [ ] SSL termination working

### **WordPress Sites**
- [ ] WordPress installed
- [ ] Database configured
- [ ] Apache/Nginx configured
- [ ] SSL certificates installed
- [ ] KHM SEO plugin installed
- [ ] GEO Tracker connection configured

### **Testing & Validation**
- [ ] Backend health checks pass
- [ ] WordPress connection tests pass
- [ ] End-to-end data flow verified
- [ ] Monitoring and alerts configured
- [ ] Backup procedures tested

---

*Deployment Guide v1.0.0 - November 12, 2025*</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/DEPLOYMENT_GUIDE.md