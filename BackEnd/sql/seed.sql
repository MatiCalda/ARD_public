SET time_zone = "-03:00";

USE ard;

INSERT INTO users (id, nombre, correo, rol_id) VALUES
('a3f1e2b0-1234-4cde-8f9a-1b2c3d4e5f60', 'Matias', 'matias.caldarone@gmail.com', 1);

INSERT INTO user_credentials (user_id, password_hash) VALUES
('a3f1e2b0-1234-4cde-8f9a-1b2c3d4e5f60', '$2y$12$KT6I8/Jcpu6a15QEZ4d.AOdFwO4GuWAV4zuEedXvHEPlw6vT1BgB6'); -- 12345$F678adw

INSERT INTO sellers (id, nombre, is_owner, contacto, telefono, comision, activo) VALUES
('b4f2e3c1-2345-5def-9g0b-2c3d4e5f6g70', 'Matias Caldarone', TRUE, 'caldaronematias@gmail.com', '1234567890', 10.00, TRUE),
('c5g3f4d2-3456-6ef0-ah1c-3d4e5f6g7h80', 'Juan Perez', FALSE, 'juan.perez@gmail.com', '0987654321', 30.00, TRUE);
