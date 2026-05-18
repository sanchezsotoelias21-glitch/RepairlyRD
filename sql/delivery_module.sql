CREATE TABLE Deliveries (
    IdDelivery INT PRIMARY KEY,
    IdReparacion INT NOT NULL,
    IdDriver INT NULL,
    Estado VARCHAR(30) NOT NULL,
    CodigoTracking VARCHAR(100) NOT NULL,
    FechaCreacion DATETIME NOT NULL,
    FechaSalida DATETIME NULL,
    FechaEntrega DATETIME NULL
);

CREATE TABLE DeliveryDrivers (
    IdDriver INT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Telefono VARCHAR(20) NOT NULL
);

CREATE TABLE DeliveryTracking (
    IdTracking INT PRIMARY KEY,
    IdDelivery INT NOT NULL,
    Latitud DECIMAL(10,7) NOT NULL,
    Longitud DECIMAL(10,7) NOT NULL,
    Fecha DATETIME NOT NULL
);