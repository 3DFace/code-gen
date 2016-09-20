<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface ClassWriter {

	function writeClass(string $className, string $phpCode);

}
