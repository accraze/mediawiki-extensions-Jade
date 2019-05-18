alter table /*_*/jade_diff_judgment
	add column jaded_damaging tinyint after jaded_judgment,
	add column jaded_goodfaith tinyint after jaded_damaging;

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jaded_damaging
	on /*_*/jade_diff_judgment
	(jaded_damaging);

-- TODO: Review this index once we have an idea of real-world usage statistics.
create index /*i*/jaded_goodfaith
	on /*_*/jade_diff_judgment
	(jaded_goodfaith);