CREATE TABLE prefix_config (
   id SERIAL PRIMARY KEY,
   name varchar(255) NOT NULL default '',
   value text NOT NULL default '',
   CONSTRAINT prefix_config_name_uk UNIQUE (name)
);

CREATE TABLE prefix_config_plugins (
   id     SERIAL PRIMARY KEY,
   plugin varchar(100) NOT NULL default 'core',
   name   varchar(100) NOT NULL default '',
   value  text NOT NULL default '',
   CONSTRAINT prefix_config_plugins_plugin_name_uk UNIQUE (plugin, name)
);

CREATE TABLE prefix_course (
   id SERIAL PRIMARY KEY,
   category integer NOT NULL default '0',
   sortorder integer NOT NULL default '0',
   password varchar(50) NOT NULL default '',
   fullname varchar(254) NOT NULL default '',
   shortname varchar(15) NOT NULL default '',
   idnumber varchar(100) NOT NULL default '',
   summary text NOT NULL default '',
   format varchar(10) NOT NULL default 'topics',
   showgrades integer NOT NULL default '1',
   modinfo text,
   newsitems integer NOT NULL default '1',
   teacher varchar(100) NOT NULL default 'Teacher',
   teachers varchar(100) NOT NULL default 'Teachers',
   student varchar(100) NOT NULL default 'Student',
   students varchar(100) NOT NULL default 'Students',
   guest integer NOT NULL default '0',
   startdate integer NOT NULL default '0',
   enrolperiod integer NOT NULL default '0',
   numsections integer NOT NULL default '1',
   marker integer NOT NULL default '0',
   maxbytes integer NOT NULL default '0',
   showreports integer NOT NULL default '0',
   visible integer NOT NULL default '1',
   hiddensections integer NOT NULL default '0',
   groupmode integer NOT NULL default '0',
   groupmodeforce integer NOT NULL default '0',
   lang varchar(10) NOT NULL default '',
   theme varchar(50) NOT NULL default '',
   cost varchar(10) NOT NULL default '',
   currency varchar(3) NOT NULL default 'USD',
   timecreated integer NOT NULL default '0',
   timemodified integer NOT NULL default '0',
   metacourse integer NOT NULL default '0',
   requested integer NOT NULL default '0',
   restrictmodules integer NOT NULL default '0',
   expirynotify integer NOT NULL default '0',
   expirythreshold integer NOT NULL default '0',
   notifystudents integer NOT NULL default '0',
   enrollable integer NOT NULL default '1',
   enrolstartdate integer NOT NULL default '0',
   enrolenddate integer NOT NULL default '0',
   enrol varchar(20) NOT NULL default '',
   defaultrole integer NOT NULL default '0'
);

CREATE UNIQUE INDEX prefix_course_category_sortorder_uk ON prefix_course (category,sortorder);
CREATE INDEX prefix_course_idnumber_idx ON prefix_course (idnumber);
CREATE INDEX prefix_course_shortname_idx ON prefix_course (shortname);

CREATE TABLE prefix_course_categories (
   id SERIAL PRIMARY KEY,
   name varchar(255) NOT NULL default '',
   description text,
   parent integer NOT NULL default '0',
   sortorder integer NOT NULL default '0',
   coursecount integer NOT NULL default '0',
   visible integer NOT NULL default '1',
   timemodified integer NOT NULL default '0',
   depth integer NOT NULL default '0',
   path varchar(255) NOT NULL default ''
);

CREATE TABLE prefix_course_display (
   id SERIAL PRIMARY KEY,
   course integer NOT NULL default '0',
   userid integer NOT NULL default '0',
   display integer NOT NULL default '0'
);

CREATE INDEX prefix_course_display_courseuserid_idx ON prefix_course_display (course,userid);

CREATE TABLE prefix_course_meta (
    id SERIAL primary key,
    parent_course integer NOT NULL,
    child_course integer NOT NULL
);

CREATE INDEX prefix_course_meta_parent_idx ON prefix_course_meta (parent_course);
CREATE INDEX prefix_course_meta_child_idx ON prefix_course_meta (child_course);

CREATE TABLE prefix_course_modules (
   id SERIAL PRIMARY KEY,
   course integer NOT NULL default '0',
   module integer NOT NULL default '0',
   instance integer NOT NULL default '0',
   section integer NOT NULL default '0',
   added integer NOT NULL default '0',
   score integer NOT NULL default '0',
   indent integer NOT NULL default '0',
   visible integer NOT NULL default '1',
   visibleold integer NOT NULL default '1',
   groupmode integer NOT NULL default '0'
);

CREATE INDEX prefix_course_modules_visible_idx ON prefix_course_modules (visible);
CREATE INDEX prefix_course_modules_course_idx ON prefix_course_modules (course);
CREATE INDEX prefix_course_modules_module_idx ON prefix_course_modules (module);
CREATE INDEX prefix_course_modules_instance_idx ON prefix_course_modules (instance);

CREATE TABLE prefix_course_sections (
   id SERIAL PRIMARY KEY,
   course integer NOT NULL default '0',
   section integer NOT NULL default '0',
   summary text,
   sequence text,
   visible integer NOT NULL default '1'
);

CREATE INDEX prefix_course_sections_coursesection_idx ON prefix_course_sections (course,section);

CREATE TABLE prefix_course_request (
   id SERIAL PRIMARY KEY,
   fullname varchar(254) NOT NULL default '',
   shortname varchar(15) NOT NULL default '',
   summary text NOT NULL default '',
   reason text NOT NULL default '',
   requester INTEGER NOT NULL default 0,
   password varchar(50) NOT NULL default ''
);

CREATE INDEX prefix_course_request_shortname_idx ON prefix_course_request (shortname);

CREATE TABLE prefix_course_allowed_modules (
   id SERIAL PRIMARY KEY,
   course INTEGER NOT NULL default 0,
   module INTEGER NOT NULL default 0
);
         
CREATE INDEX prefix_course_allowed_modules_course_idx ON prefix_course_allowed_modules (course);
CREATE INDEX prefix_course_allowed_modules_module_idx ON prefix_course_allowed_modules (module);

CREATE TABLE prefix_event (
   id SERIAL PRIMARY KEY,
   name varchar(255) NOT NULL default '',
   description text,
   format integer NOT NULL default '0',
   courseid integer NOT NULL default '0',
   groupid integer NOT NULL default '0',
   userid integer NOT NULL default '0',
   repeatid integer NOT NULL default '0',
   modulename varchar(20) NOT NULL default '',
   instance integer NOT NULL default '0',
   eventtype varchar(20) NOT NULL default '',
   timestart integer NOT NULL default '0',
   timeduration integer NOT NULL default '0',
   visible integer NOT NULL default '1',
   uuid char(36) NOT NULL default '',
   sequence integer NOT NULL default '1',
   timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_event_courseid_idx ON prefix_event (courseid);
CREATE INDEX prefix_event_userid_idx ON prefix_event (userid);
CREATE INDEX prefix_event_timestart_idx ON prefix_event (timestart);
CREATE INDEX prefix_event_timeduration_idx ON prefix_event (timeduration);


CREATE TABLE prefix_grade_category (
  id SERIAL PRIMARY KEY,
  name varchar(64) default NULL,
  courseid integer NOT NULL default '0',
  drop_x_lowest integer NOT NULL default '0',
  bonus_points integer NOT NULL default '0',
  hidden integer NOT NULL default '0',
  weight decimal(5,2) default '0.00'
);

CREATE INDEX prefix_grade_category_courseid_idx ON prefix_grade_category (courseid);

CREATE TABLE prefix_grade_exceptions (
  id SERIAL PRIMARY KEY,
  courseid integer  NOT NULL default '0',
  grade_itemid integer  NOT NULL default '0',
  userid integer  NOT NULL default '0'
);

CREATE INDEX prefix_grade_exceptions_courseid_idx ON prefix_grade_exceptions (courseid);


CREATE TABLE prefix_grade_item (
  id SERIAL PRIMARY KEY,
  courseid integer default NULL,
  category integer default NULL,
  modid integer default NULL,
  cminstance integer default NULL,
  scale_grade float(11) default '1.0000000000',
  extra_credit integer NOT NULL default '0',
  sort_order integer  NOT NULL default '0'
);

CREATE INDEX prefix_grade_item_courseid_idx ON prefix_grade_item (courseid);

CREATE TABLE prefix_grade_letter (
  id SERIAL PRIMARY KEY,
  courseid integer NOT NULL default '0',
  letter varchar(8) NOT NULL default 'NA',
  grade_high decimal(6,2) NOT NULL default '100.00',
  grade_low decimal(6,2) NOT NULL default '0.00'
);

CREATE INDEX prefix_grade_letter_courseid_idx ON prefix_grade_letter (courseid);

CREATE TABLE prefix_grade_preferences (
  id SERIAL PRIMARY KEY,
  courseid integer default NULL,
  preference integer NOT NULL default '0',
  value integer NOT NULL default '0'
);

CREATE UNIQUE INDEX prefix_grade_prefs_courseidpref_uk ON prefix_grade_preferences (courseid,preference);

CREATE TABLE prefix_groups (
   id SERIAL PRIMARY KEY,
   courseid integer NOT NULL default '0',
   name varchar(255) NOT NULL default '',
   description text,
   password varchar(50) NOT NULL default '',
   lang varchar(10) NOT NULL default '',
   theme varchar(50) NOT NULL default '',
   picture integer NOT NULL default '0',
   hidepicture integer NOT NULL default '0',
   timecreated integer NOT NULL default '0',
   timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_groups_idx ON prefix_groups (courseid);

CREATE TABLE prefix_groups_members (
   id SERIAL PRIMARY KEY,
   groupid integer NOT NULL default '0',
   userid integer NOT NULL default '0',
   timeadded integer NOT NULL default '0'
);

CREATE INDEX prefix_groups_members_idx ON prefix_groups_members (groupid);
CREATE INDEX prefix_groups_members_userid_idx ON prefix_groups_members (userid);

CREATE TABLE prefix_log (
   id SERIAL PRIMARY KEY,
   time integer NOT NULL default '0',
   userid integer NOT NULL default '0',
   ip varchar(15) NOT NULL default '',
   course integer NOT NULL default '0',
   module varchar(20) NOT NULL default '',
   cmid integer NOT NULL default '0',
   action varchar(40) NOT NULL default '',
   url varchar(100) NOT NULL default '',
   info varchar(255) NOT NULL default ''
);

CREATE INDEX prefix_log_coursemoduleaction_idx ON prefix_log (course,module,action);
CREATE INDEX prefix_log_timecoursemoduleaction_idx ON prefix_log (time,course,module,action);
CREATE INDEX prefix_log_courseuserid_idx ON prefix_log (course,userid);
CREATE INDEX prefix_log_userid_idx ON prefix_log (userid);
CREATE INDEX prefix_log_info_idx ON prefix_log (info);

CREATE TABLE prefix_log_display (
   id SERIAL PRIMARY KEY,
   module varchar(20) NOT NULL default '',
   action varchar(40) NOT NULL default '',
   mtable varchar(30) NOT NULL default '',
   field varchar(200) NOT NULL default ''
);
CREATE INDEX prefix_log_display_moduleaction ON prefix_log_display (module,action);

CREATE TABLE prefix_message (
   id SERIAL PRIMARY KEY,
   useridfrom integer NOT NULL default '0',
   useridto integer NOT NULL default '0',
   message text,
   format integer NOT NULL default '0',
   timecreated integer NOT NULL default '0',
   messagetype varchar(50) NOT NULL default ''
);

CREATE INDEX prefix_message_useridfrom_idx ON prefix_message (useridfrom);
CREATE INDEX prefix_message_useridto_idx ON prefix_message (useridto);

CREATE TABLE prefix_message_read (
   id SERIAL PRIMARY KEY,
   useridfrom integer NOT NULL default '0',
   useridto integer NOT NULL default '0',
   message text,
   format integer NOT NULL default '0',
   timecreated integer NOT NULL default '0',
   timeread integer NOT NULL default '0',
   messagetype varchar(50) NOT NULL default '',
   mailed integer NOT NULL default '0'
);

CREATE INDEX prefix_message_read_useridfrom_idx ON prefix_message_read (useridfrom);
CREATE INDEX prefix_message_read_useridto_idx ON prefix_message_read (useridto);

CREATE TABLE prefix_message_contacts (
   id SERIAL PRIMARY KEY,
   userid integer NOT NULL default '0',
   contactid integer NOT NULL default '0',
   blocked integer NOT NULL default '0'
);

CREATE INDEX prefix_message_contacts_useridcontactid_idx ON prefix_message_contacts (userid,contactid);

CREATE TABLE prefix_modules (
   id SERIAL PRIMARY KEY,
   name varchar(20) NOT NULL default '',
   version integer NOT NULL default '0',
   cron integer NOT NULL default '0',
   lastcron integer NOT NULL default '0',
   search varchar(255) NOT NULL default '',
   visible integer NOT NULL default '1'
);

CREATE INDEX prefix_modules_name_idx ON prefix_modules (name);

CREATE TABLE prefix_scale (
   id SERIAL PRIMARY KEY,
   courseid integer NOT NULL default '0',
   userid integer NOT NULL default '0',
   name varchar(255) NOT NULL default '',
   scale text,
   description text,
   timemodified integer NOT NULL default '0'
);

CREATE TABLE prefix_sessions2 (
    sesskey VARCHAR(255) NOT NULL default '',
    expiry TIMESTAMP NOT NULL,
    expireref VARCHAR(255),
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL,
    sessdata TEXT,
CONSTRAINT prefix_sess_ses_pk PRIMARY KEY (sesskey)
);

CREATE INDEX prefix_sess_exp_ix ON prefix_sessions2 (expiry);

CREATE INDEX prefix_sess_exp2_ix ON prefix_sessions2 (expireref);

CREATE TABLE prefix_timezone (
  id SERIAL PRIMARY KEY,
  name varchar(100) NOT NULL default '',
  year integer NOT NULL default '0',
  tzrule varchar(20) NOT NULL default '',
  gmtoff integer NOT NULL default '0',
  dstoff integer NOT NULL default '0',
  dst_month integer NOT NULL default '0',
  dst_startday integer NOT NULL default '0',
  dst_weekday integer NOT NULL default '0',
  dst_skipweeks integer NOT NULL default '0',
  dst_time varchar(5) NOT NULL default '00:00',
  std_month integer NOT NULL default '0',
  std_startday integer NOT NULL default '0',
  std_weekday integer NOT NULL default '0',
  std_skipweeks integer NOT NULL default '0',
  std_time varchar(5) NOT NULL default '00:00'
);


CREATE TABLE prefix_cache_filters (
   id SERIAL PRIMARY KEY,
   filter varchar(32) NOT NULL default '',
   version integer NOT NULL default '0',
   md5key varchar(32) NOT NULL default '',
   rawtext text,
   timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_cache_filters_filtermd5key_idx ON prefix_cache_filters (filter,md5key);

CREATE INDEX prefix_scale_courseid_idx ON prefix_scale (courseid);


CREATE TABLE prefix_cache_text (
   id SERIAL PRIMARY KEY,
   md5key varchar(32) NOT NULL default '',
   formattedtext text,
   timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_cache_text_md5key_idx ON prefix_cache_text (md5key);

--
-- Table structure for table `user`
--
-- When updating field length, modify
-- truncate_userinfo() in moodlelib.php
--
CREATE TABLE prefix_user (
   id SERIAL PRIMARY KEY,
   auth varchar(20) NOT NULL default 'manual',
   confirmed integer NOT NULL default '0',
   policyagreed integer NOT NULL default '0',
   deleted integer NOT NULL default '0',
   username varchar(100) NOT NULL default '',
   password varchar(32) NOT NULL default '',
   idnumber varchar(64) default NULL,
   firstname varchar(20) NOT NULL default '',
   lastname varchar(20) NOT NULL default '',
   email varchar(100) NOT NULL default '',
   emailstop integer NOT NULL default '0',
   icq varchar(15) default NULL,
   skype varchar(50) default NULL,
   yahoo varchar(50) default NULL,
   aim varchar(50) default NULL,
   msn varchar(50) default NULL,
   phone1 varchar(20) default NULL,
   phone2 varchar(20) default NULL,
   institution varchar(40) default NULL,
   department varchar(30) default NULL,
   address varchar(70) default NULL,
   city varchar(20) default NULL,
   country char(2) default NULL,
   lang varchar(10) NOT NULL default '',
   theme varchar(50) NOT NULL default '',
   timezone varchar(100) NOT NULL default '99',
   firstaccess integer NOT NULL default '0',
   lastaccess integer NOT NULL default '0',
   lastlogin integer NOT NULL default '0',
   currentlogin integer NOT NULL default '0',
   lastip varchar(15) default NULL,
   secret varchar(15) default NULL,
   picture integer default NULL,
   url varchar(255) default NULL,
   description text,
   mailformat integer NOT NULL default '1',
   maildigest integer NOT NULL default '0',
   maildisplay integer NOT NULL default '2',
   htmleditor integer NOT NULL default '1',
   ajax integer NOT NULL default '1',
   autosubscribe integer NOT NULL default '1',
   trackforums integer NOT NULL default '0',
   timemodified integer NOT NULL default '0'
);

CREATE UNIQUE INDEX prefix_user_username_uk ON prefix_user (username);
CREATE INDEX prefix_user_idnumber_idx ON prefix_user (idnumber);
CREATE INDEX prefix_user_auth_idx ON prefix_user (auth);
CREATE INDEX prefix_user_deleted_idx ON prefix_user (deleted);
CREATE INDEX prefix_user_confirmed_idx ON prefix_user (confirmed);
CREATE INDEX prefix_user_firstname_idx ON prefix_user (firstname);
CREATE INDEX prefix_user_lastname_idx ON prefix_user (lastname);
CREATE INDEX prefix_user_city_idx ON prefix_user (city);
CREATE INDEX prefix_user_country_idx ON prefix_user (country);
CREATE INDEX prefix_user_lastaccess_idx ON prefix_user (lastaccess);
CREATE INDEX prefix_user_email_idx ON prefix_user (email);

CREATE TABLE prefix_user_admins (
   id SERIAL PRIMARY KEY,
   userid integer NOT NULL default '0'
);

CREATE INDEX prefix_user_admins_userid_idx ON prefix_user_admins (userid);

CREATE TABLE prefix_user_preferences (
   id SERIAL PRIMARY KEY,
   userid integer NOT NULL default '0',
   name varchar(50) NOT NULL default '',
   value varchar(255) NOT NULL default ''
);

CREATE INDEX prefix_user_preferences_useridname_idx ON prefix_user_preferences (userid,name);

CREATE TABLE prefix_user_students (
   id SERIAL PRIMARY KEY,
   userid integer NOT NULL default '0',
   course integer NOT NULL default '0',
   timestart integer NOT NULL default '0',
   timeend integer NOT NULL default '0',
   time integer NOT NULL default '0',
   timeaccess integer NOT NULL default '0',
   enrol varchar (20) NOT NULL default ''
);

CREATE UNIQUE INDEX prefix_user_students_courseuserid_uk ON prefix_user_students (course,userid);
CREATE INDEX prefix_user_students_userid_idx ON prefix_user_students (userid);
CREATE INDEX prefix_user_students_enrol_idx ON prefix_user_students (enrol);
CREATE INDEX prefix_user_students_timeaccess_idx ON prefix_user_students (timeaccess);

CREATE TABLE prefix_user_teachers (
   id SERIAL PRIMARY KEY,
   userid integer NOT NULL default '0',
   course integer NOT NULL default '0',
   authority integer NOT NULL default '3',
   role varchar(40) NOT NULL default '',
   editall integer NOT NULL default '1',
   timestart integer NOT NULL default '0',
   timeend integer NOT NULL default '0',
   timemodified integer NOT NULL default '0',
   timeaccess integer NOT NULL default '0',
   enrol varchar (20) NOT NULL default ''
);

CREATE UNIQUE INDEX prefix_user_teachers_courseuserid_uk ON prefix_user_teachers (course,userid);
CREATE INDEX prefix_user_teachers_userid_idx ON prefix_user_teachers (userid);
CREATE INDEX prefix_user_teachers_enrol_idx ON prefix_user_teachers (enrol);

CREATE TABLE prefix_user_coursecreators (
   id SERIAL8 PRIMARY KEY,
   userid int8  NOT NULL default '0'
);

CREATE INDEX prefix_user_coursecreators_userid_idx ON prefix_user_coursecreators (userid);

CREATE TABLE adodb_logsql (
   created timestamp NOT NULL,
   sql0 varchar(250) NOT NULL,
   sql1 text NOT NULL,
   params text NOT NULL,
   tracer text NOT NULL,
   timer decimal(16,6) NOT NULL
);

CREATE TABLE prefix_stats_daily (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   stattype varchar(20) NOT NULL default 'activity',
   stat1 INTEGER NOT NULL default 0,
   stat2 INTEGER NOT NULL default 0,
   CHECK (stattype::text = 'enrolments' OR stattype::text = 'activity' OR stattype::text = 'logins')
);

CREATE INDEX prefix_stats_daily_courseid_idx ON prefix_stats_daily (courseid);
CREATE INDEX prefix_stats_daily_timeend_idx ON prefix_stats_daily (timeend);

CREATE TABLE prefix_stats_weekly (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   stattype varchar(20) NOT NULL default 'activity',
   stat1 INTEGER NOT NULL default 0,
   stat2 INTEGER NOT NULL default 0,
   CHECK (stattype::text = 'enrolments' OR stattype::text = 'activity' OR stattype::text = 'logins')
);

CREATE INDEX prefix_stats_weekly_courseid_idx ON prefix_stats_weekly (courseid);
CREATE INDEX prefix_stats_weekly_timeend_idx ON prefix_stats_weekly (timeend);

CREATE TABLE prefix_stats_monthly (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   stattype varchar(20) NOT NULL default 'activity',
   stat1 INTEGER NOT NULL default 0,
   stat2 INTEGER NOT NULL default 0,
   CHECK (stattype::text = 'enrolments' OR stattype::text = 'activity' OR stattype::text = 'logins')
);

CREATE INDEX prefix_stats_monthly_courseid_idx ON prefix_stats_monthly (courseid);
CREATE INDEX prefix_stats_monthly_timeend_idx ON prefix_stats_monthly (timeend);

CREATE TABLE prefix_stats_user_daily (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   userid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   statsreads INTEGER NOT NULL default 0,
   statswrites INTEGER NOT NULL default 0,
   stattype varchar(30) NOT NULL default ''
);
         
CREATE INDEX prefix_stats_user_daily_courseid_idx ON prefix_stats_user_daily (courseid);
CREATE INDEX prefix_stats_user_daily_userid_idx ON prefix_stats_user_daily (userid);
CREATE INDEX prefix_stats_user_daily_roleid_idx ON prefix_stats_user_daily (roleid);
CREATE INDEX prefix_stats_user_daily_timeend_idx ON prefix_stats_user_daily (timeend);

CREATE TABLE prefix_stats_user_weekly (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   userid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   statsreads INTEGER NOT NULL default 0,
   statswrites INTEGER NOT NULL default 0,
   stattype varchar(30) NOT NULL default ''
);
         
CREATE INDEX prefix_stats_user_weekly_courseid_idx ON prefix_stats_user_weekly (courseid);
CREATE INDEX prefix_stats_user_weekly_userid_idx ON prefix_stats_user_weekly (userid);
CREATE INDEX prefix_stats_user_weekly_roleid_idx ON prefix_stats_user_weekly (roleid);
CREATE INDEX prefix_stats_user_weekly_timeend_idx ON prefix_stats_user_weekly (timeend);

CREATE TABLE prefix_stats_user_monthly (
   id SERIAL PRIMARY KEY,
   courseid INTEGER NOT NULL default 0,
   userid INTEGER NOT NULL default 0,
   roleid INTEGER NOT NULL default 0,
   timeend INTEGER NOT NULL default 0,
   statsreads INTEGER NOT NULL default 0,
   statswrites INTEGER NOT NULL default 0,
   stattype varchar(30) NOT NULL default ''
);
         
CREATE INDEX prefix_stats_user_monthly_courseid_idx ON prefix_stats_user_monthly (courseid);
CREATE INDEX prefix_stats_user_monthly_userid_idx ON prefix_stats_user_monthly (userid);
CREATE INDEX prefix_stats_user_monthly_roleid_idx ON prefix_stats_user_monthly (roleid);
CREATE INDEX prefix_stats_user_monthly_timeend_idx ON prefix_stats_user_monthly (timeend);

CREATE TABLE prefix_post (
  id SERIAL PRIMARY KEY,
  module varchar(20) NOT NULL default '',
  userid INTEGER NOT NULL default 0,
  courseid INTEGER NOT NULL default 0,
  groupid INTEGER NOT NULL default 0,
  moduleid INTEGER NOT NULL default 0,
  coursemoduleid INTEGER NOT NULL default 0,
  subject varchar(128) NOT NULL default '',
  summary text,
  content text,
  uniquehash varchar(128) NOT NULL default '',
  rating INTEGER NOT NULL default 0,
  format INTEGER NOT NULL default 0,
  publishstate varchar(10) CHECK (publishstate IN ('draft','site','public')) NOT NULL default 'draft',
  lastmodified INTEGER NOT NULL default '0',
  created INTEGER NOT NULL default '0'
);

CREATE INDEX prefix_id_user_idx ON prefix_post  (id, courseid);
CREATE INDEX prefix_post_lastmodified_idx ON prefix_post (lastmodified);
CREATE INDEX prefix_post_module_idx ON prefix_post (moduleid);
CREATE INDEX prefix_post_subject_idx ON prefix_post (subject);

CREATE TABLE prefix_tags (
  id SERIAL PRIMARY KEY,
  type varchar(255) NOT NULL default 'official',
  userid INTEGER NOT NULL default 0,
  text varchar(255) NOT NULL default ''
);
CREATE INDEX prefix_tags_typeuserid_idx ON prefix_tags (type, userid);
CREATE INDEX prefix_tags_text_idx ON prefix_tags (text);

CREATE TABLE prefix_blog_tag_instance (
  id SERIAL PRIMARY KEY,
  entryid integer NOT NULL default 0,
  tagid integer NOT NULL default 0,
  groupid integer NOT NULL default 0,
  courseid integer NOT NULL default 0,
  userid integer NOT NULL default 0,
  timemodified integer NOT NULL default 0
);
CREATE INDEX prefix_bti_entryid_idx ON prefix_blog_tag_instance (entryid);
CREATE INDEX prefix_bti_tagid_idx ON prefix_blog_tag_instance (tagid);

# Roles tables   
CREATE TABLE prefix_role (   
  id SERIAL PRIMARY KEY,     
  name varchar(255) NOT NULL default '',     
  shortname varchar(100) NOT NULL default '',     
  description text NOT NULL default '',      
  sortorder integer NOT NULL default '0'     
);   
CREATE INDEX prefix_role_sortorder_idx ON prefix_role (sortorder);
         
CREATE TABLE prefix_context (    
  id SERIAL PRIMARY KEY,     
  contextlevel integer NOT NULL default 0,      
  instanceid integer NOT NULL default 0      
);
CREATE INDEX prefix_context_instanceid_idx ON prefix_context (instanceid);
CREATE UNIQUE INDEX prefix_context_contextlevelinstanceid_idx ON prefix_context (contextlevel, instanceid);
         
CREATE TABLE prefix_role_assignments (   
  id SERIAL PRIMARY KEY,     
  roleid integer NOT NULL default 0,     
  contextid integer NOT NULL default 0,      
  userid integer NOT NULL default 0,     
  hidden integer NOT NULL default 0,     
  timestart integer NOT NULL default 0,      
  timeend integer NOT NULL default 0,    
  timemodified integer NOT NULL default 0,   
  modifierid integer NOT NULL default 0,     
  enrol varchar(20) NOT NULL default '',     
  sortorder integer NOT NULL default '0'     
);  
CREATE INDEX prefix_role_assignments_roleid_idx ON prefix_role_assignments (roleid);
CREATE INDEX prefix_role_assignments_contextidid_idx ON prefix_role_assignments (contextid);
CREATE INDEX prefix_role_assignments_userid_idx ON prefix_role_assignments (userid);
CREATE UNIQUE INDEX prefix_role_assignments_contextidroleiduserid_idx ON prefix_role_assignments (contextid, roleid, userid);
CREATE INDEX prefix_role_assignments_sortorder_idx ON prefix_role_assignments (sortorder);
       
CREATE TABLE prefix_role_capabilities (      
  id SERIAL PRIMARY KEY,     
  contextid integer NOT NULL default 0,      
  roleid integer NOT NULL default 0,     
  capability varchar(255) NOT NULL default '',   
  permission integer NOT NULL default 0,     
  timemodified integer NOT NULL default 0,   
  modifierid integer NOT NULL default 0      
);   
CREATE INDEX prefix_role_capabilities_roleid_idx ON prefix_role_capabilities (roleid);
CREATE INDEX prefix_role_capabilities_contextid_idx ON prefix_role_capabilities (contextid);
CREATE INDEX prefix_role_capabilities_modifierid_idx ON prefix_role_capabilities (modifierid);
CREATE UNIQUE INDEX prefix_role_capabilities_roleidcontextidcapability_idx ON prefix_role_capabilities (roleid, contextid, capability);       
              
CREATE TABLE prefix_role_allow_assign (    
  id SERIAL PRIMARY KEY,     
  roleid integer NOT NULL default '0',   
  allowassign integer NOT NULL default '0'      
);   
CREATE INDEX prefix_role_allow_assign_roleid_idx ON prefix_role_allow_assign (roleid);
CREATE INDEX prefix_role_allow_assign_allowassign_idx ON prefix_role_allow_assign (allowassign);
CREATE UNIQUE INDEX prefix_role_allow_assign_roleidallowassign_idx ON prefix_role_allow_assign (roleid, allowassign);

CREATE TABLE prefix_role_allow_override (    
  id SERIAL PRIMARY KEY,     
  roleid integer NOT NULL default '0',   
  allowoverride integer NOT NULL default '0'      
);   
CREATE INDEX prefix_role_allow_override_roleid_idx ON prefix_role_allow_override (roleid);
CREATE INDEX prefix_role_allow_override_allowoverride_idx ON prefix_role_allow_override (allowoverride);
CREATE UNIQUE INDEX prefix_role_allow_override_roleidallowoverride_idx ON prefix_role_allow_override (roleid, allowoverride);
       
CREATE TABLE prefix_capabilities (   
  id SERIAL PRIMARY KEY,     
  name varchar(255) NOT NULL default '',     
  captype varchar(50) NOT NULL default '',   
  contextlevel integer NOT NULL default 0,   
  component varchar(100) NOT NULL default '',     
  riskbitmask integer NOT NULL default 0   
);         
CREATE UNIQUE INDEX prefix_capabilities_name_idx ON prefix_capabilities (name);

CREATE TABLE prefix_role_names (     
  id SERIAL PRIMARY KEY,     
  roleid integer NOT NULL default 0,     
  contextid integer NOT NULL default 0,      
  text text NOT NULL default ''      
);
CREATE INDEX prefix_role_names_roleid_idx ON prefix_role_names (roleid);
CREATE INDEX prefix_role_names_contextid_idx ON prefix_role_names (contextid);
CREATE UNIQUE INDEX prefix_role_names_roleidcontextid_idx ON prefix_role_names (roleid, contextid);
       
CREATE TABLE prefix_user_lastaccess ( 
  id SERIAL PRIMARY KEY,     
  userid integer NOT NULL default 0,
  courseid integer NOT NULL default 0, 
  timeaccess integer NOT NULL default 0
);

CREATE INDEX prefix_user_lastaccess_userid_idx ON prefix_user_lastaccess (userid);
CREATE INDEX prefix_user_lastaccess_courseid_idx ON prefix_user_lastaccess (courseid);
CREATE UNIQUE INDEX prefix_user_lastaccess_useridcourseid_idx ON prefix_user_lastaccess (userid, courseid);
      
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('user', 'view', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'user report', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'view', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'update', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'enrol', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'report log', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'report live', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'report outline', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'report participation', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('course', 'report stats', 'course', 'fullname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'write', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'read', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'add contact', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'remove contact', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'block contact', 'user', 'firstname||\' \'||lastname');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('message', 'unblock contact', 'user', 'firstname||\' \'||lastname');


CREATE TABLE prefix_user_info_field (
    id BIGSERIAL,
    name VARCHAR(255) NOT NULL default '',
    datatype VARCHAR(255) NOT NULL default '',
    categoryid BIGINT NOT NULL default 0,
    sortorder BIGINT NOT NULL default 0,
    required SMALLINT NOT NULL default 0,
    locked SMALLINT NOT NULL default 0,
    visible SMALLINT NOT NULL default 0,
    defaultdata TEXT,
CONSTRAINT prefix_userinfofiel_id_pk PRIMARY KEY (id)
);

COMMENT ON TABLE prefix_user_info_field IS 'Customisable user profile fields';

CREATE TABLE prefix_user_info_category (
    id BIGSERIAL,
    name VARCHAR(255) NOT NULL default '',
    sortorder BIGINT NOT NULL default 0,
CONSTRAINT prefix_userinfocate_id_pk PRIMARY KEY (id)
);

COMMENT ON TABLE prefix_user_info_category IS 'Customisable fields categories';

CREATE TABLE prefix_user_info_data (
    id BIGSERIAL,
    userid BIGINT NOT NULL default 0,
    fieldid BIGINT NOT NULL default 0,
    data TEXT NOT NULL,
CONSTRAINT prefix_userinfodata_id_pk PRIMARY KEY (id)
);

COMMENT ON TABLE prefix_user_info_data IS 'Data for the customisable user fields';