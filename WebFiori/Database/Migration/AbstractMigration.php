<?php
namespace WebFiori\Database\Migration;

use WebFiori\Database\Database;

/**

 * @author Ibrahim
 */
abstract class AbstractMigration {
    private $id;
    private $name;
    private $appliedAt;
    private $order;
    public function __construct(string $name, int $order) {
        $this->setName($name);
        $this->setOrder($order);
        $this->setAppliedAt(date('Y-m-d H:i:s'));
        $this->setId(-1);
    }
    public function getAppliedAt() : string {
        return $this->appliedAt;
    }
    public function setAppliedAt(string $date) {
        $this->appliedAt = $date;
    }
    public function getOrder() : int {
        return $this->order;
    }
    public function setOrder(int $order) {
        $this->order = $order;
    }
    public function getName() : string {
        return $this->name;
    }
    public function setName(string $name) {
        $this->name = $name;
    }
    public function setId(int $id) {
        $this->id = $id;
    }
    public function getId() : int {
        return $this->id;
    }
    public abstract function up(Database $schema);
    public abstract function down(Database $schema);
}
