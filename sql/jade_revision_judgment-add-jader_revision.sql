-- Only one judgment per revision.
create unique index /*i*/jader_revision
	on /*_*/jade_revision_judgment
	(jader_revision);