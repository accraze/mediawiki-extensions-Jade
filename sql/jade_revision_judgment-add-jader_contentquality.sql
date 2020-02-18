alter table /*_*/jade_revision_judgment add column jader_contentquality int unsigned after jader_judgment;

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jader_contentquality
	on /*_*/jade_revision_judgment
	(jader_contentquality);