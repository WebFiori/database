CREATE TABLE if not exists `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` varchar(45) NOT NULL DEFAULT 'Me',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `posts` (`title`, `author`) VALUES 
('First Post', 'Me'),
('Second Post', 'You'),
('Third Post', 'Them');