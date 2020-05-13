-- Link table between revision judgment pages and the revision they target.
create table /*_*/jade_revision_label (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jaderl_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jaderl_rev_id int unsigned not null,
	-- Page ID of the judgment.
	jaderl_page_id int unsigned not null,
	-- Content quality
	jaderl_contentquality int unsigned
) /*$wgDBTableOptions*/;

-- Only one judgment per revision.
create unique index /*i*/jaderl_revision
	on /*_*/jade_revision_label
	(jaderl_rev_id);

-- Covering index, get all data when joining on target revision.
create index /*i*/jaderl_covering
	on /*_*/jade_revision_label
	(jaderl_rev_id, jaderl_page_id, jaderl_contentquality);

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jaderl_contentquality
	on /*_*/jade_revision_label
	(jaderl_contentquality);
