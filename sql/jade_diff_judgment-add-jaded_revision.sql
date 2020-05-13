-- Only one judgment per revision.
create unique index /*i*/jaded_revision
	on /*_*/jade_diff_judgment
	(jaded_revision);