<?php

class sanitize
{
    public function contains_forbidden_characters($input) {        
        if (preg_match('/[a-zA-Z]/', $input)) {
            return false;
        }
        return true; 
    }
}

?>
