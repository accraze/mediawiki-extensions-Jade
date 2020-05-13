-- Covering index, get all data when joining on target revision.
create index /*i*/jader_covering
	on /*_*/jade_revision_judgment
	(jader_revision, jader_judgment, jader_contentquality);