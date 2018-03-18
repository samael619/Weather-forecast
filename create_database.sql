SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema weatherDB
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `weatherDB` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;
USE `weatherDB` ;

-- -----------------------------------------------------
-- Table `weatherDB`.`city`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `weatherDB`.`city` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `name` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `weatherDB`.`weather`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `weatherDB`.`weather` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `dt` DATETIME NOT NULL,
  `city_id` INT NOT NULL,
  `temp` FLOAT NOT NULL,
  `pressure` FLOAT NOT NULL,
  `humidity` INT NOT NULL,
  `description` VARCHAR(100) NOT NULL,
  `clouds` INT NOT NULL,
  `wind_speed` FLOAT NOT NULL,
  `wind_deg` FLOAT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_weather_city_idx` (`city_id` ASC),
  CONSTRAINT `fk_weather_city`
    FOREIGN KEY (`city_id`)
    REFERENCES `weatherDB`.`city` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
