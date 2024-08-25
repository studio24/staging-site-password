<?php
/**
 * Command line script to generate a password hash
 */

echo "Enter the password: ";
$line = trim(fgets(STDIN));

echo "Creating a password hash for: " . $line . PHP_EOL;
echo "Password hash is: " . PHP_EOL;
echo password_hash($line, PASSWORD_DEFAULT) . PHP_EOL;
exit(0);
