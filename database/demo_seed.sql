USE es_espanol_facil;

-- Original synthetic starter content written for the public portfolio demo.
INSERT INTO levels (code, name, description, sort_order) VALUES
    ('A1', 'Начальный', 'Знакомство, повседневные слова и простые фразы.', 1),
    ('A2', 'Базовый', 'Общение в знакомых бытовых ситуациях.', 2),
    ('B1', 'Средний', 'Связная речь о событиях, планах и впечатлениях.', 3),
    ('B2', 'Выше среднего', 'Аргументация и понимание сложных текстов.', 4),
    ('C1', 'Продвинутый', 'Гибкое и точное использование языка.', 5);

INSERT INTO texts (level_id, title, content, word_count, topic) VALUES
    (1, 'Una mañana en Madrid',
     'Lucía vive cerca de una plaza tranquila. Por la mañana abre la ventana, prepara café y camina hasta la panadería. Allí saluda a Carlos y compra pan. Después vuelve a casa para estudiar español.',
     34, 'Повседневная жизнь');

INSERT INTO grammar_topics (level_id, title, explanation, examples, sort_order) VALUES
    (1, 'Глагол ser в настоящем времени',
     'Ser используется, когда мы называем человека или предмет, говорим о происхождении, профессии или постоянном признаке.',
     'Yo soy estudiante.\nTú eres de Madrid.\nElla es profesora.', 1);

INSERT INTO vocabulary (level_id, text_id, spanish, russian, theme) VALUES
    (1, 1, 'la casa', 'дом', 'Город и дом'),
    (1, 1, 'la plaza', 'площадь', 'Город и дом'),
    (1, 1, 'la panadería', 'пекарня', 'Город и дом'),
    (1, 1, 'el café', 'кофе', 'Еда'),
    (1, 1, 'el pan', 'хлеб', 'Еда'),
    (1, 1, 'caminar', 'гулять, идти пешком', 'Действия');

INSERT INTO exercises
    (level_id, parent_type, parent_id, exercise_type, question, options,
     correct_answer, explanation, sort_order)
VALUES
    (1, 'text', 1, 'multiple_choice', 'Куда утром идёт Лусия?',
     '["В библиотеку", "В пекарню", "На вокзал"]', 'В пекарню',
     'В тексте сказано: camina hasta la panadería.', 1),
    (1, 'grammar', 1, 'fill_blank', 'Дополните: Yo ___ estudiante.',
     NULL, 'soy', 'Для yo используется форма soy.', 1),
    (1, 'vocabulary', 1, 'matching', 'Соедините слова и переводы.',
     '{"la casa":"дом","el pan":"хлеб","la plaza":"площадь"}',
     '{"la casa":"дом","el pan":"хлеб","la plaza":"площадь"}',
     'Эти слова встречаются в демонстрационном тексте.', 1),
    (1, 'test', 0, 'multiple_choice', 'Выберите приветствие.',
     '["Hola", "Mesa", "Libro"]', 'Hola', 'Hola означает «привет».', 1),
    (2, 'test', 0, 'multiple_choice', 'Дополните: Ayer ___ al museo.',
     '["voy", "fui", "iré"]', 'fui', 'Для завершённого действия используется fui.', 2),
    (3, 'test', 0, 'multiple_choice', 'Выберите связку причины.',
     '["porque", "aunque", "mientras"]', 'porque', 'Porque вводит причину.', 3),
    (4, 'test', 0, 'multiple_choice', 'Выберите корректный вариант: Si tuviera tiempo, ...',
     '["viajaría más", "viajo ayer", "viajaré antes"]', 'viajaría más',
     'В условной конструкции используется condicional.', 4),
    (5, 'test', 0, 'multiple_choice', 'Какое слово ближе всего к «несмотря на»?',
     '["a pesar de", "debido a", "acerca de"]', 'a pesar de',
     'A pesar de выражает уступку.', 5);
