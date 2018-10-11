-- Link table between revision judgment pages and the revision they target.
create table /*_*/jade_revision_judgment (
	-- Primary key.  This is for internal use and not guaranteed to be stable.
	jader_id int unsigned not null primary key auto_increment,
	-- Revision ID being judged.
	jader_revision int unsigned not null,
	-- Page ID of the judgment.
	jader_judgment int unsigned not null
) /*$wgDBTableOptions*/;

-- Join judgments by target revision.  Covers judgment page ID.
create unique index /*i*/jader_revision_judgment
	on /*_*/jade_revision_judgment
	(jader_revision, jader_judgment);
