A Sane DataMapper for PHP
=======

## Goals

- Simple API
- Limited Magic
- Faster Development

## Configuration

```php
$manager    = new Manager();
$driver     = new Pgsql();
$connection = new Connection('pgsql', )

$manager->setup($driver, 'pgsql');
$manager->connect($connection, 'Dotink\Community');
```

## Repositories

Repositories are the central objects in Redub.  They interface with the manager to get the
information you need and allow you to easily manage entities.

### Creating a Repository

```php
namespace Dotink\Community;

class Forums extends Redub\Repository
{
	static protected $entityName = 'Dotink\Community\Forum';
}
```

### Using a Repository

#### Instantiation

```php
$forums = new Dotink\Community\Forums($manager)
```

#### Finding a Single Entity

If the primary key is a single column, you can just pass the value:

```php
$forum = $forums->find(1);
```

Compound primary keys can be done with arrays:

```php
$forum = $forums->find(['category' => 1, 'position' => 0]);
```

Unique keys can also be used with the array syntax:

```php
$forum = $forums->find(['slug' => $slug]);
```

#### Building a Collection of Entities

Collections are built by creating criteria.  When you build a collection from a repository you
need to provide a builder which accepts and modifies the criteria:

```php
$closed_forums = $forums->build(function($criteria) {
	$criteria->where(['closed =' => TRUE]);
    $criteria->order(['dateClosed' => 'desc']);
    $criteria->limit(5);
});
```

You can use the `fetch()` method to run convention based build methods on the repository.  For
example:

```php
protected function buildClosed(Criteria)
{
	$criteria->where(['closed =' => TRUE]);
}
```

Once the build method is added you can execute the following:

```php
$closed_forums = $forums->fetch('closed');
```

You can also overload the limit and order using `fetch()`:

```php
$closed_forums = $forums->fetch('closed', 10, ['dateClosed' => 'asc']);
```

### Using a Collection

Collections can inherit the same criteria which was used to build them.  These criteria can be
modified to *add* new criteria or change *existing* criteria:

```php
$popular_forums = $open_forums->modify(function($criteria) {
    $criteria->where(['threadCount >=' => 1000]);
});
```

It is important to note that `modify()` will always return a new collection.

#### Adding to a Collection

```php
$favorite_movies = $user->getFavoriteMovies();

$favorite_movies->add($movie);
```
