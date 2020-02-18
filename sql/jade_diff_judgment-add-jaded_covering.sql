-- Covering index, get all data when joining on target revision.
create index /*i*/jaded_covering
	on /*_*/jade_diff_judgment
	(jaded_revision, jaded_judgment, jaded_damaging, jaded_goodfaith);