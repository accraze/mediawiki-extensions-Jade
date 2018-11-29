-- Link table between revision judgment pages and the revision they target.
create table /*_*/jade_revision_judgment (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jader_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jader_revision int unsigned not null,
	-- Page ID of the judgment.
	jader_judgment int unsigned not null,
	-- Content quality
	jader_contentquality int unsigned
) /*$wgDBTableOptions*/;

-- Only one judgment per revision.
create unique index /*i*/jader_revision
	on /*_*/jade_revision_judgment
	(jader_revision);

-- Covering index, get all data when joining on target revision.
create index /*i*/jader_covering
	on /*_*/jade_revision_judgment
	(jader_revision, jader_judgment, jader_contentquality);

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jader_contentquality
	on /*_*/jade_revision_judgment
	(jader_contentquality);
