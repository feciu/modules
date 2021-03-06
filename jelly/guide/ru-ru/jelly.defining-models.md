# Описание моделей

Первым делом, с чего следует начинать при работе с любой ORM библиотекой - это описание Ваших
моделей. Jelly разделяет модель на несколько отдельных компонентов, добиваясь последовательной
и расширяемой работы API.

Рассмотрим для начала примерную модель:

	class Model_Post extends Jelly_Model
	{
		public static function initialize(Jelly_Meta $meta)
		{
			$meta->table('posts')
				 ->fields(array(
					 'id' => new Field_Primary,
					 'name' => new Field_String,
					 'body' => new Field_Text,
					 'status' => new Field_Enum(array(
						 'choices' => array('draft', 'review', 'published'))),
					 'author' => new Field_BelongsTo,
					 'tags' => new Field_ManyToMany,
				 ));
		}
	}

Как видно, чтобы модель была обработата Jelly, она должна:

 * Расширять класс `Jelly_Model`
 * Содержать метод `initialize()` который принимает объект `$meta` класса `Jelly_Meta`
 * Добавлять свойства объекту `$meta`, чтобы определить поля, таблицу, ключи и остальные необходимые
данные

Метод `initialize()` вызывается один раз для каждой модели, а meta-объект модели хранится статически.
Для того чтобы получить какие-то данные об определённой модели, можно использовать `Jelly::meta('model')`.

[!!] Большинство описанных тут вещей приведены для справки - они опциональны и имеют разумные 
значения по-умолчанию.

## Jelly Fields

Jelly определяет [много типов полей](jelly.field-types), которые охватывают большинство типов данных,
обычно используемых при описании таблиц баз данных.

Объект `fields` в Jelly содержит всю логику для получения, установки и сохранения информации в базу
данных

Поскольку все отношения обрабатываются в рамках отношений полей, то можно реализовывать собственную
логику в модели путем [определения пользовательских полей](jelly.extending-field).

### Далее [Загрузка и получение данных](jelly.loading-and-listing)