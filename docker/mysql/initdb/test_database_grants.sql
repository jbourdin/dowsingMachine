-- Runs once, on the first start of an empty database volume.
-- Lets the app user create and use the PHPUnit database (created by
-- `make test.db`) and the per-process suffixed databases added by paratest.
GRANT ALL PRIVILEGES ON `dowsing_machine_test%`.* TO 'dowsing_machine'@'%';
