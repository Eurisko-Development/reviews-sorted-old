<?php

namespace Reviews\Database;

use Reviews\Database\Exceptions\DatabaseException;

class Query
{
	protected $connection;

	protected $having = [];

	protected $group = [];

	protected $where = [];

	protected $join = [];

	protected $order = [];

	protected $select = ['*'];

	protected $from;

	protected $offset;

	protected $limit;

	public function __construct($connection, $from) 
	{
		$this->connection = $connection;
		$this->from = $from;

		return $this;
	}

	public static function raw($raw) 
	{
		return 'raw::'. $raw;
	}

	public function select($select) 
	{
		if ($this->select[0] == '*') unset($this->select[0]);

		$this->select[] = $select;
		return $this;
	}

	public function where($column, $operator, $value = null, $boolean = 'AND') 
	{
		if (!$value) {
			$value = $operator;
			$operator = '=';
		}

		$this->where[] = [
			'type' => 'basic',
			'column' => $column,
			'operator' => $operator,
			'value' => $value,
			'boolean' => $boolean
		];

		return $this;
	}

	public function orWhere($column, $operator, $value = null) 
	{
		if (!$value) {
			$value = $operator;
			$operator = '=';
		}

		return $this->where($column, $operator, $value, 'OR');
	}

	public function whereIn($column, $values, $boolean = 'AND') 
	{
		$this->where[] = [
			'type' => 'in',
			'column' => $column,
			'value' => $values,
			'boolean' => $boolean
		];

		return $this;
	}

	public function whereBetween($column, $values, $boolean = 'AND') 
	{
		$this->where[] = [
			'type' => 'between',
			'column' => $column,
			'value' => $values,
			'boolean' => $boolean
		];

		return $this;
	}

	public function orderBy($column, $direction = 'asc') 
	{
		$this->order[$column] = $direction;

		return $this;
	}

	public function latest($column = 'created_at') 
	{
		return $this->orderBy($column, 'DESC');
	}

	public function oldest($column = 'created_at') 
	{
		return $this->orderBy($column, 'ASC');
	}

	public function rand() 
	{
		$this->order['RAND()'] = '';
		return $this;
	}

	public function skip($offset) 
	{
		$this->offset = $offset;
		return $this;
	}

	public function take($limit) 
	{
		$this->limit = $limit;
		return $this;
	}

	public function groupBy($group) 
	{
		$this->group[] = $group;
	}

	public function having($having) 
	{
		$this->having[] = $having;
	}

	public function create($data) 
	{		
		$sql = "INSERT INTO ";
	   $sql .= "`{$this->from}` ";
	
		$sql .= "(". implode(', ', array_map(function ($value) {
			return "`{$value}`";
		}, array_keys($data))) .") ";

		$sql .= "VALUES ";
		$sql .= "(". implode(', ', array_map(function ($value) {
			return "'$value'";
		}, array_values($data))) .") ";
		
		$statement = $this->connection->prepare($sql);		
		$result = $statement->execute($data);
	
		if ( ! $result) {
			throw new DatabaseException('Could not insert new data');
		}

		$this->insert_id = $this->connection->lastInsertId();

		return $result;
	}

	public function update($data) 
	{
		$fields = array_map(function ($key) {
			return "`{$key}` = :update_{$key}";
		}, array_keys($data));

		$sql = "UPDATE ";
		$sql .= "`{$this->from}` ";
		$sql .= "SET ". implode(', ', $fields) ." ";

		if (sizeof($this->where)) {
			$sql .= "WHERE 1=1 AND ". $this->buildWhere($this->where);
		}

		foreach ($data as $key => $value) {
			$values['update_'. $key] = $value;
		}

		$data = array_merge($this->buildWhereValues($this->where), $values);
		
		$statement = $this->connection->prepare($sql);
		$result = $statement->execute($data);
		
		if ( ! $result) {
			throw new DatabaseException('Could not update a data');
		}

		return $result;
	}

	public function all() 
	{
		$this->limit = null;

		return $this->get();
	}

	public function get() 
	{
		$sql = "SELECT ";

		$sql .= implode(', ', $this->select) ." ";

		$sql .= "FROM `{$this->from}` ";

		if (sizeof($this->where)) {
			$sql .= "WHERE 1=1 AND ". $this->buildWhere($this->where) ." ";
		}

		if (sizeof($this->group)) {
			$sql .= "GROUP BY ". implode(', ', $this->group) ." ";
		}

		if (sizeof($this->having)) {
			$sql .= "HAVING ". implode(', ', $this->having) ." ";
		}

		if (sizeof($this->order)) {
			$ordering = [];
			foreach ($this->order as $column => $direction) {
				$ordering[] = "$column $direction";
			}
			$sql .= "ORDER BY ". implode(', ', $ordering) ." ";
		}

		if ($this->limit) {
			$sql .= "LIMIT ". ($this->offset ? "{$this->offset}, ": '') ."{$this->limit}";
		}
		

		
		$statement = $this->connection->prepare($sql);
		 $statement->execute($this->buildWhereValues($this->where));

		return $statement->fetchAll();
	}

	public function count() 
	{
		$sql = "SELECT ";

		$sql .= "COUNT(*) AS total ";

		$sql .= "FROM `{$this->from}` ";

		if (sizeof($this->where)) {
			$sql .= "where created_at >= now()-interval 3 month";
		}
			
		$statement = $this->connection->prepare($sql);
		$statement->execute();

		$result = $statement->fetch(\PDO::FETCH_ASSOC);		

		return $result['total'];
	}


	public function first() 
	{
		$this->take(1);

		$results = $this->get();

		return $results[0];
	}

	public function paginate($limit) 
	{
		$this->take($limit);

		if (isset($_GET['paged'])) {
			$page = (int) $_GET['paged'];

			$offset = ($page * $limit) - $limit;
			$this->skip($offset);
		}

		return $this->get();
	}

	public function query() 
	{
		return $this;
	}

	protected function cleanColumn($column)
	{
		return preg_replace("/[^A-z0-9\_]*/", "", $column);
	}

	protected function buildWhere($where) 
	{
		$sql = "";


		
		foreach ($where as $data) {
			if (is_array($data) && ! isset($data['column'])) {
				$sql .= $this->buildWhere($data);
			}

			$column = preg_match("/^raw::/", $data['column']) ? str_replace('raw::', '', $data['column']) : "`{$data['column']}`";

			if ($data['type'] == 'basic') {
				$sql .= "$column {$data['operator']} :where_{$this->cleanColumn($data['column'])} {$data['boolean']} ";
			}
			if (in_array($data['type'], ['in', 'between'])) {
				$columns = [];
				foreach ($data['value'] as $index => $value) {
					$columns[] = ":where_{$this->cleanColumn($data['column'])}_{$index}";
				}

				if ($data['type'] == 'in') {
					$sql .= sprintf('%s %s (%s) %s', $column, strtoupper($data['type']), implode(", ", $columns), $data['boolean']);
				}

				if ($data['type'] == 'between') {
					$sql .= sprintf('%s %s %s %s', $column, strtoupper($data['type']), implode(" AND ", $columns), $data['boolean']);
				}
			}
		}

		 $sql = preg_replace("/(.*)AND|OR\s$/", "$1", $sql);

		return "({$sql})";
	}

	protected function buildWhereValues($where)
	{
		$values = [];

		foreach ($where as $data) {
			if (is_array($data) && ! isset($data['column'])) {
				$values = array_merge($this->buildWhereValues($data), $values);
			
			} 

			if (in_array($data['type'], ['in', 'between'])) {
				$columns = [];
				foreach ($data['value'] as $index => $value) {
					$values['where_'. $this->cleanColumn($data['column']) .'_'. $index] = $value;
				}

				continue;
			}

			$values['where_'. $this->cleanColumn($data['column'])] = $data['value'];
		}
      
		return $values;
	}
}