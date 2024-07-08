# PHP MongoDB TTL Index & Change Stream Listener Example

This repository provides an example PHP application showcasing the usage of MongoDB TTL indexes and change streams. It
includes a command-line script to listen for changes in MongoDB collections with TTL indexes, handling insert, update,
replace, and delete events.

## Features

- Demonstrates setting up TTL indexes for automatic document expiration.
- Illustrates using change streams to monitor collection changes in real-time.
- Handles various CRUD operations and tracks document changes efficiently.

## Requirements

- PHP >= 8.2
- MongoDB PHP Driver
- MongoDB server with replica set configured (for change streams)

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/php-mongo-ttl-index-change-stream-listener-example.git
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Configure MongoDB connection in `MongoStreamListenerCommand.php`.

## Usage

Run the listener command to start monitoring MongoDB collection changes:

```bash
php bin/console dev:mongo-stream-listener
```

![](docs/assets/README-1720454397217.png)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
