USE es_espanol_facil;

SELECT 'levels' AS table_name, COUNT(*) AS total FROM levels
UNION ALL
SELECT 'texts', COUNT(*) FROM texts
UNION ALL
SELECT 'grammar_topics', COUNT(*) FROM grammar_topics
UNION ALL
SELECT 'vocabulary', COUNT(*) FROM vocabulary
UNION ALL
SELECT 'exercises', COUNT(*) FROM exercises;
