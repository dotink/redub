<?php

	class People extends Redub\ORM\Repository
	{
		/**
		 *
		 */
		static public function config($map)
		{
			return Redub\ORM\Map::create('People', 'people', 'id')
				-> id([
					'format' => 'int',
					'source' => 'native'
				])

				-> firstName([
					'format' => 'string',
					'column' => 'first_name'
				])

				-> lastName([
					'format' => 'string'
				])

				-> team([
					'format' => 'hasOne',
					'remote' => 'Team',
					'routes' => ['team' => 'id']
				])

				-> team2([
					'format' => 'hasOne',
					'remote' => 'Team',
					'routes' => ['team2' => 'id']
				])

				-> groups([
					'format' => 'hasMany',
					'remote' => 'Group',
					'routes' => ['user_groups' => ['id' => 'user', 'group' => 'id']],
					'clause' => ['active ==' => TRUE]
				], [
					'is_student ==' => TRUE
				], [

				])
			;
		}
	}
