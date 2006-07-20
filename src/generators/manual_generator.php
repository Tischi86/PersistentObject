<?php
/**
 * File containing the ezcPersistentManualGenerator class
 *
 * @package PersistentObject
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Does not generate ID's. Simply uses the ID set in the object
 * when new objects are saved.
 *
 * This is useful don't want any automatic id generation.
 *
 * @package PersistentObject
 * @access private
 */
class ezcPersistentManualGenerator extends ezcPersistentIdentifierGenerator
{
    private $id = null;

    /**
     * Check if the object is persistent already
     *
     * Called in the beginning of the save process.
     *
     * Persistent objects that are being saved must not exist in the database already.
     *
     * @param ezcPersistentObjectDefinition $def
     * @param array(key=>value)
     * @return void
     */
    public function checkPersistence( ezcPersistentObjectDefinition $def, ezcDbHandler $db, array $state )
    {
        // store id
        $this->id = $state[$def->idProperty->propertyName];

                // check if there is an object with this id already
        $q = $db->createSelectQuery();
        $q->select( '*' )->from( $def->table )
            ->where( $q->expr->eq( $def->idProperty->columnName,
                                   $q->bindValue( $this->id ) ) );
        try
        {
            $stmt = $q->prepare();
            $stmt->execute();
        }
        catch ( PDOException $e )
        {
            throw new ezcPersistentQueryException( $e->getMessage() );
        }

        $row = $stmt->fetch( PDO::FETCH_ASSOC );
        $stmt->closeCursor();
        if ( $row !== false ) // we got a result
        {
            return true;
        }

        return false;
    }

    /**
     * Sets the correct id on the insert query.
     *
     * @param ezcPersistentObjectDefinition $def
     * @param ezcDbHandler $db
     * @param ezcQueryInsert $q
     * @return void
     */
    public function preSave( ezcPersistentObjectDefinition $def, ezcDbHandler $db, ezcQueryInsert $q )
    {
        $q->set( $def->idProperty->columnName, $q->bindValue( $this->id ) );
    }

    /**
     * Returns the integer value of the generated identifier for the new object.
     *
     * Called right after execution of the insert query.
     *
     * @param ezcPersistentObjectDefinition $def
     * @param ezcDbHandler $db
     * @return int
     */
    public function postSave( ezcPersistentObjectDefinition $def, ezcDbHandler $db )
    {
        return $this->id;
    }
}

?>