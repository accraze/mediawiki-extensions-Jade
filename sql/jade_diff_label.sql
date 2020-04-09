-- Link table between diff label pages and the revision they target.
create table /*_*/jade_diff_label (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jadedl_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jadedl_rev_id int unsigned not null,
	-- Page ID of the label.
	jadedl_page_id int unsigned not null,
	-- Judged to be damaging?
	jadedl_damaging tinyint,
	-- Judged to be good faith?
	jadedl_goodfaith tinyint
) /*$wgDBTableOptions*/;

-- Only one judgment per revision.
create unique index /*i*/jadedl_rev_id
 on /*_*/jade_diff_label
   (jadedl_rev_id);

