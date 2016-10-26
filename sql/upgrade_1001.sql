
DELETE FROM `civicrm_value_recruitment_6` WHERE `recruitment_12` NOT IN (SELECT `ov2`.`value` FROM `civicrm_option_value` `ov2` WHERE `option_group_id` = '80');

DELETE FROM `civicrm_option_value` WHERE `option_group_id` = '97' AND `value` NOT IN (SELECT `ov3`.`value` FROM (SELECT `ov2`.* FROM `civicrm_option_value` `ov2` WHERE `ov2`.`option_group_id` = '80') AS `ov3`);


DELETE FROM `civicrm_option_value` WHERE `option_group_id` = '80';
DELETE FROM `civicrm_option_group` WHERE `id` = '80';

UPDATE `civicrm_option_group` SET `name` = 'Recruitment', `title` = 'Recruitment' WHERE `id` = '97';