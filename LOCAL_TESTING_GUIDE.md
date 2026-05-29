# Local Testing Guide for Laravel Local DB Setup Package

This guide walks you through testing the `laravel-local-db-setup` package locally before pushing to GitHub.

## Setup Steps

### 1. Create or Use a Test Laravel Project

Create a new Laravel project for testing:

```bash
cd ~/Sites
composer create-project laravel/laravel test-db-setup
cd test-db-setup
```

Or use an existing project.

### 2. Configure Composer Local Repository

Add the local package path to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-db-provisioner",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "laravel/framework": "^11.0",
        "subhanraj/laravel-db-provisioner": "@dev"
    }
}
```

### 3. Install the Package

```bash
composer install
```

You should see the package symlinked in `vendor/subhanraj/laravel-db-provisioner`.

Verify the symlink:
```bash
ls -la vendor/subhanraj/
```

### 4. Test the Package Registration

Check that the service provider was auto-discovered:

```bash
php artisan package:discover
```

You should see your package listed.

### 5. Run the Command

Now test the main command:

```bash
php artisan db:provision
```

You'll be prompted for your local MariaDB admin password. Enter it.

### 6. Verify Results

Check your `.env` file:
```bash
grep -i "DB_" .env
```

You should see:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_db_setup_local
DB_USERNAME=test_db_setup_local
DB_PASSWORD=<random_16_chars>
```

Verify the database was created in MariaDB:
```bash
mysql -h 127.0.0.1 -u admin -p -e "SHOW DATABASES LIKE '%_local';"
```

Verify the user was created:
```bash
mysql -h 127.0.0.1 -u admin -p -e "SELECT User, Host FROM mysql.user WHERE User LIKE '%test_db_setup%';"
```

### 7. Test Laravel Integration

Run Laravel migrations to confirm the database connection works:

```bash
php artisan migrate
```

The migrations should execute successfully with your newly created database and user.

## Troubleshooting During Testing

### Command not found
- Run `php artisan list` to see all available commands
- Verify the package appears in `vendor/subhan/laravel-local-db-setup`
- Try: `php artisan cache:clear` and `php artisan config:clear`

### Permission errors
- Check MariaDB is running: `brew services list | grep mariadb`
- Verify admin user exists: `mysql -u admin -p -h 127.0.0.1`

### Symlink not created
- Manually verify: `ls -l vendor/subhan/laravel-local-db-setup`
- If it's not symlinked, remove and reinstall: `composer remove subhan/laravel-local-db-setup && composer install`

### Cannot update .env
- Ensure the file has write permissions: `chmod 644 .env`
- Check the .env file is not corrupted

## Development Workflow

As you make changes to the package:

1. Edit files in `~/Sites/laravel-local-db-setup/src/`
2. Since it's symlinked, changes are immediately reflected in your test project
3. No need to reinstall or run `composer install` after edits (unless you change `composer.json`)
4. Test in your Laravel project: `php artisan db:provision`

## Testing the Full Workflow

To test the complete workflow:

1. **Delete the test database and user** (optional, to test from scratch):
   ```bash
   mysql -u admin -p -h 127.0.0.1 -e "DROP DATABASE test_db_setup_local; DROP USER 'test_db_setup_local'@'127.0.0.1'; DROP USER 'test_db_setup_local'@'localhost';"
   ```

2. **Reset the `.env` file**:
   ```bash
   rm .env
   ```

3. **Run the command again**:
   ```bash
   php artisan db:provision
   ```

4. **Verify everything works**: `php artisan migrate`

## Making Edits to the Package

### Common changes to test:

**Changing the database naming logic:**
- Edit `src/Commands/ProvisionDatabaseCommand.php` → `slugify()` method
- Test with different app names in `.env`

**Adding validation or error handling:**
- Edit any of the methods
- Re-run `php artisan db:provision` to see changes

**Updating the success message:**
- Edit `outputSuccess()` method
- Run command to see new output

## Before Publishing to GitHub

1. **Test one more time from scratch**:
   ```bash
   cd ~/Sites
   rm -rf test-db-setup
   composer create-project laravel/laravel test-db-setup
   cd test-db-setup
   # Update composer.json with the repository
   composer install
   php artisan db:provision
   ```

2. **Run any unit tests** (if you add them):
   ```bash
   vendor/bin/phpunit
   ```

3. **Update README.md** with final setup instructions

4. **Commit and tag** your first release:
   ```bash
   cd ~/Sites/laravel-db-provisioner
   git init
   git add .
   git commit -m "Initial commit: Laravel DB Provisioner"
   git tag v1.0.0
   ```

5. **Push to GitHub**:
   ```bash
   git remote add origin https://github.com/subhanraj/laravel-db-provisioner.git
   git push -u origin main
   git push --tags
   ```

## Removing the Local Test Setup

When done testing:

```bash
# Option 1: Keep the package, remove test project
cd ~/Sites && rm -rf test-db-setup

# Option 2: Keep everything (for future testing)
# No action needed
```

## Quick Reference Commands

```bash
# Test project location
~/Sites/test-db-setup

# Package location
~/Sites/laravel-db-provisioner

# Run the package command
cd ~/Sites/test-db-setup && php artisan db:provision

# View package files in project
ls -la ~/Sites/test-db-setup/vendor/subhanraj/laravel-db-provisioner/

# Check if symlinked
ls -l ~/Sites/test-db-setup/vendor/subhanraj/laravel-db-provisioner | head -1

# View latest MariaDB databases
mysql -u admin -p -h 127.0.0.1 -e "SHOW DATABASES ORDER BY CREATE_TIME DESC LIMIT 5;"
```

