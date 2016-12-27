<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

trait EqualsBySerialize {

	/**
	 * @param mixed $val
	 * @return bool
	 */
	function equals($val){
		if(!$this instanceof \JsonSerializable){
			throw new \InvalidArgumentException("Cant JSON-serialize ".get_class($this)." instances");
		}
		return $val instanceof static && $this->jsonSerialize() === $val->jsonSerialize();
	}

}
