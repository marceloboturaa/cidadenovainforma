UPDATE forum_areas
SET active = 0,
    updated_at = NOW()
WHERE slug = 'estudantes';
