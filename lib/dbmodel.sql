
ALTER TABLE `player` ADD `player_bet` ENUM( 'no', 'little', 'big' ) NULL DEFAULT NULL ;

ALTER TABLE `player` ADD `player_points_bet` INT NOT NULL DEFAULT '0',
ADD `player_points_captured` INT UNSIGNED NOT NULL DEFAULT '0',
ADD `player_points_remaining` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(10) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(10) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `combo` (
  `combo_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `combo_player_id` int(10) unsigned NOT NULL,
  `combo_display` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`combo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;





