CREATE TABLE message(
    id int AUTO_INCREMENT PRIMARY KEY,
    target VARCHAR(16) NOT NULL,
    source VARCHAR(16) NOT NULL,
    text MEDIUMTEXT NOT NULL,
    sent TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE message
    add constraint check_min_source_length check (length(source) >= 4);

ALTER TABLE message
    add constraint check_min_target_length check (length(target) >= 4);
