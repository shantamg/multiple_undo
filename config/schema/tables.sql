CREATE TABLE `undos` ( 
  `id` int(11) NOT NULL, 
  `description` varchar(30) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=latin1; 

CREATE TABLE `undo_fields` ( 
  `id` int(11) NOT NULL auto_increment, 
  `undo_id` int(11) NOT NULL, 
  `undo_table_id` int(11) NOT NULL, 
  `field_key` varchar(30) NOT NULL, 
  `field_val` longtext NOT NULL, 
  PRIMARY KEY  (`id`) 
) ENGINE=MyISAM  DEFAULT CHARSET=latin1; 

CREATE TABLE `undo_tables` ( 
  `id` int(11) NOT NULL auto_increment, 
  `undo_id` int(11) NOT NULL, 
  `name` varchar(30) NOT NULL, 
  `action` smallint(1) NOT NULL, 
  `record_id` int(11) NOT NULL, 
  PRIMARY KEY  (`id`) 
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;