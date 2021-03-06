<?php
namespace Reviews\Database;
use Reviews\Database\Query;
use Reviews\Database\Connection;
class Model
{
	protected $table;
	protected $prefix = '';
	protected $primaryKey = 'id';
	protected $fillable = [];
	protected $attributes = [];
	protected $original = [];
	protected $timestamps = true;
	protected $casts = [];
	public $exists;
	protected $isBooted = false;
	protected $query;
	public function __construct($attributes = []) 
	{
		$this->fill($attributes);
		
		$this->boot();
	}
	protected function boot() {
		if ($this->isBooted) return;
		$this->query = new Query(Connection::getInstance(), $this->getTable());
		$this->isBooted = true;
	}
	public function getTable() 
	{
		global $wpdb;
		if ( ! $this->table) {
			preg_match("/([^\\\]+)$/i", get_class($this), $class);
			$class[1] = preg_replace("/y$/", "ie", $class[1]);
			$this->table = ($this->prefix ? $this->prefix .'_' : '') . strtolower($class[1]) .'s';
		}
		return $wpdb->prefix . $this->table;
	}
	public static function create($data) 
	{
		$model = new static();
		$model->fill($data);
		$model->save();
		return $model;
	}
	public static function find($value) 
	{
		$model = new static();
		$data = $model->query->where($model->getPrimaryKey(), $value)
					 		 ->first();
		
		return $model->buildModel($data);
	}
	public function buildModel($item)
	{
		if (!$item) {
			return null;
		}
		$model = new static;
		
		$item = $model->castAttributes($item);
		$model->dbFill($item);
		$model->exists = true;
		$pk = $model->getPrimaryKey();
		$model->syncOriginal();
		if (isset($item[$pk])) {
			$model->query->where($pk, $item[$pk]);
		}
		return $model;
	}
	protected function buildModels($items)
	{
		$results = [];
		foreach ($items as $item) {
			$results[] = $this->buildModel($item);
		}
		return $results;
	}
	public function paginate($limit) 
	{
		$items = $this->query->paginate($limit);
		
		return $this->buildModels($items);
	}
	public function fill($attributes) 
	{
		
		
		
		foreach ($attributes as $attribute => $value) {
			if (!in_array($attribute, $this->fillable)) {
				continue;
			}
			$this->setAttribute($attribute, $value);
		}
	}
	protected function dbFill($attributes)
	{
		foreach ($attributes as $attribute => $value) {
			$this->setAttribute($attribute, $value);
		}
	}
	public function save() 
	{
		// Create new record
		if ( ! $this->exists) {
			if ($this->timestamps) {
				$now = date('Y-m-d H:i:s');
				$this->attributes = array_merge([
					'created_at' => $now, 
					'updated_at' => $now
				], $this->attributes);
			}
			$data = $this->uncastAttributes($this->attributes);	
			
			$affected = $this->query->create($data);
		
			$this->{$this->getPrimaryKey()} = $this->query->insert_id;
			$this->exists = true;	
			
			return $affected;
		}
		$update = [];
		foreach ($this->attributes as $attribute => $value) {
			if ($this->original[$attribute] == $value || !isset($this->original[$attribute])) {
				continue;
			}
			$update[$attribute] = $value;
		}
		if (sizeof($update)) {
			if ($this->timestamps) {
				$update = array_merge($update, [
					'updated_at' => date('Y-m-d H:i:s')
				]);
				$this->attributes['updated_at'] = $update['updated_at'];
			}
			$update = $this->uncastAttributes($update);
			return $this->query->update($update);
		}
	}
	protected function syncOriginal()
	{
		foreach ($this->attributes as $attribute => $value) {
			$this->original[$attribute] = $value;
		}
	}
	protected function setAttribute($attribute, $value) 
	{
		$this->attributes[$attribute] = $value;
	}
	protected function getAttribute($attribute) 
	{
		if (!isset($this->attributes[$attribute])) {
			return null;
		}
		$value = $this->attributes[$attribute];
		if (isset($this->casts[$attribute])) {
			$type = $this->casts[$attribute];
			$value = $this->castAttribute($value, $type);
		}
		
		return $value;
	}
	protected function castAttributes($data) 
	{	
		foreach ($data as $attribute => &$value) {
			if ( empty($this->casts[$attribute])) {
				continue;
			}
			
			$value = $this->castAttribute($value, $this->casts[$attribute]);
			
		}
		
			
		return $data;
	}
	protected function uncastAttributes($data)
	{
		foreach ($data as $attribute => &$value) {
			if (!isset($this->casts[$attribute])) {
				continue;
			}
			$value = $this->uncastAttribute($value, $this->casts[$attribute]);
		}
		return $data;
	}
	protected function castAttribute($value, $type)
	{
		if ($type == 'array') {
			$value = is_string($value) ? json_decode($value, true) : (array) $value;
		}
			
		return $value;
	}
	protected function uncastAttribute($value, $type)
	{
		if ($type == 'array') {
			$value = is_array($value) ? json_encode($value) : $value;
		}
		return $value;
	}
	public function getPrimaryKey() 
	{
		return $this->primaryKey;
	}
	public function __get($attribute) 
	{
		return $this->getAttribute($attribute);
	}
	public function __set($attribute, $value) 
	{
		return $this->setAttribute($attribute, $value);
	}
	public function __call($method, $parameters)
    {
        $result = call_user_func_array([$this->query, $method], $parameters);
        if (in_array($method, ['count'])) {
        	return $result;
        }
        if (in_array($method, ['get', 'all'])) {
			return $this->buildModels($result);
        }
        if (in_array($method, ['first'])) {
        	return $test=$this->buildModel($result);
		
        }
			
        return $this;
    }
	public static function __callStatic($method, $parameters) 
	{
		$instance = new static;
        return call_user_func_array([$instance, $method], $parameters);
	}
}