<?php
 
if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

require_once INC_PATH.'pwTools/string/encoding.php';

/**
 * This is a single node to construct a tree-structure of nodes.
 * Parent pointers will be updated automatically.
 * 
 * @author Piiit <pitiz29a@gmail.com>
 */
class Node {
	private $name = "";
	private $parent = null;			 
	private $children = array();
	private $data;
	
	/**
	 * Creates a Node
	 * @param string 	$name 	optional node-name to specify similar types of nodes.
	 * @param mixed 	$data 	optional data-fields (can be anything)
	 * @throws InvalidArgumentException
	 */
	public function __construct($name = "", $data = null) {
		$this->setName($name);
		$this->setData($data);
	}
	
	/**
	 * Replaces whitespaces except space with escaped characters and creates a string with name and data.
	 * @return string
	 */
	public function __toString() {
		$data = (is_array($this->getData()) ? ("[".implode(",", $this->getData())."]") : ("'".$this->getData())."'");
		$data = pw_s2e_whiteSpace($data);
		return "[Node: ".$this->getName().($data ? "=$data" : "")."]";
	}

	/**
	 * Returns the name of the node.
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Sets the name of the node. 
	 * TODO Node-names allowed characters: a..z, A..Z, 0..9, _
	 * @param string 	$name
	 * @throws InvalidArgumentException
	 */
	public function setName($name) {
		if (!is_string($name)) {
			throw new InvalidArgumentException("Node-Name must be of type string!");
		}
		$this->name = $name;
	}

	/**
	 * Returns a list of children or an empty array if this is a leaf-node.
	 * @return array List of children or empty array.
	 */
	public function getChildren() {
		if ($this->hasChildren()) {
			return $this->children;
		}
		throw new OutOfRangeException("The node $this has no children!");
	}
	
	/**
	 * Gets the data of the node.
	 * @return mixed Data stored in the node (can be anything).
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Returns the parent-node.
	 * @return Node|null Returns the parent-node or null if this the root-node.
	 */
	public function getParent() {
		return $this->parent;
	}
	
	/**
	 * Returns the root of this node or null if this node is the root.
	 * @return Node|null
	 */
	public function getRoot() {
		if ($this->isRoot()) {
			return null;
		}
		$node = $this;
		while (! $node->isRoot()) {
			$node = $node->getParent();
		}
		return $node;
	}
	
	/**
	 * Check if this node is the root.
	 * @return boolean
	 */
	public function isRoot() {
		return ($this->parent === null);
	}
	
	/**
	 * Adds a child to the list
	 * @param Node $child
	 * @throws InvalidArgumentException
	 */
	public function addChild($child) {
		if ($child instanceof Node) {
			$child->_setParent($this);
			$this->children[] = $child;
		} else {
			throw new InvalidArgumentException("A child must be a Node!");
		}
	}

	/**
	 * Set the data of the node (can be anything, checks must be done by caller).
	 * @param mixed $data
	 */
	public function setData($data) {
		$this->data = $data;
	}
	
	/**
	 * Check if this node has children.
	 * @return boolean
	 */
	public function hasChildren() {
		if (sizeof($this->children) > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check if this node is a leaf-node.
	 * @return boolean
	 */
	public function isLeaf() {
		return (!$this->hasChildren());
	}
	
	/**
	 * Get next sibling if existent or null if not.
	 * @return Node|NULL
	 */
	public function getNextSibling() {
		if($this->isRoot()) {
			return null;
		}
		$pnode = $this->getParent();
		$pnodeChildren = $pnode->getChildren();
		$cur = $this->getChildIndex();
		if (isset($pnodeChildren[$cur+1])) {
			return $pnodeChildren[$cur+1];
		}
		return null;
	}
	
	/**
	 * Get previous sibling if existent or null if not.
	 * @return Node|NULL
	 */
	public function getPreviousSibling() {
		$pnode = $this->getParent();
		$pnodeChildren = $pnode->getChildren();
		$cur = $this->getChildIndex();
		if (isset($pnodeChildren[$cur-1])) {
			return $pnodeChildren[$cur-1];
		}
		return null;
	}
	
	/**
	 * Get first child from children's list or null if no children exist.
	 * @return Node|NULL
	 */
	public function getFirstChild() {
		if ($this->hasChildren()) {
			return $this->children[0];
		}
		throw new OutOfRangeException("This node $this has no children!");
	}
	
	/**
	 * Get last child from children's list or null if no children exist.
	 * @return Node|NULL
	 */
	public function getLastChild() {
		if ($this->hasChildren()) {
			return $this->children[sizeof($this->children)-1];
		}
		throw new OutOfRangeException("This node $this has no children!");
	}
	
	/**
	 * Check if this node is inside of a specific ancestor, 
	 * ex. check if a wiki-node is inside a "nowiki"-node.
	 * @param string $name
	 * @return boolean 
	 */
	public function isInside($name) {
		$node = $this->getParent();
		while($node != null) {
			if ($node->getName() == $name) {
				return true;
			}
			$node = $node->getParent();
		}
		return false;
	}
	
	/**
	 * Returns an array of nodes with the given name.
	 * @param string $name	Search for nodes with this name.
	 * @return array|NULL Array of nodes if found or null.
	 */
	public function getNodesByName($name) {
		return $this->_getNodesByNameREC($name, $this);
	}

	/**
	 * Finds the next sibling and goes down to the same child in its child-branch.
	 * For example, this is useful to find the neighbor data-field inside a wiki-table.
	 * (tablerow = next sibling & table-cell = child) 
	 * @param Node $child
	 * @return Node
	 */
	public function getNextSiblingSameChild($child) {
		$sibling = $this->getNextSibling();
		if (!$sibling)
			return null;
		$childIndex = $child->getChildIndex();
		$child = $sibling->getChildByIndex($childIndex);
		return $child;
	}
	
	/**
	 * Finds the previous sibling and goes down to the same child in his child-branch.
	 * For example, this is useful to find the neighbor data-field inside a wiki-table.
	 * (tablerow = next sibling & table-cell = child)
	 * @param Node $child
 	 * @return Node
	 */
	public function getPreviousSiblingSameChild($child) {
		$sibling = $this->getPreviousSibling();
		if (!$sibling)
			return null;
		$childIndex = $child->getChildIndex();
		$child = $sibling->getChildByIndex($childIndex);
		return $child;
	}
	
	public function removeKeepChildren() {
		// TODO remove this node, but connect it's children with the parent node.
	}
	
	public function remove() {
		// TODO remove this node and all it's children.
	}
	
	/**
	 * Fetch the child with specified index from children's list or null if not existent.
	 * @param int $childIndex	array index
	 * @return NULL|Node
	 */
	public function getChildByIndex($childIndex) {
		if (!isset($this->children[$childIndex]))
			throw new OutOfRangeException("No child with array index $childIndex avaiable!");
		return $this->children[$childIndex];
	}
	
	/**
	 * Returns the index which this node has inside parent's children's list.
	 * @throws Exception If this node can't be found inside parent's children's list.
	 * @return int Array-index.
	 */
	public function getChildIndex() {
		$parent = $this->getParent();
		
		if ($parent && $parent->hasChildren()) {
			foreach($parent->getChildren() as $index => $child) {
				if ($child === $this) {
					return $index;
				}
			}
		}
		
		// Error, if not found, because this node asked for a list of all children of their own parents, 
		// they must have at least this one linked.
		throw new OutOfBoundsException("Array index not found inside parent's child stack!");
	}

	/**
	 * Sets the parent of a child, when this node gets added to a parent-node.
	 * @param Node $parent
	 * @throws InvalidArgumentException	If the node is not a instance of Node.
	 */
	private function _setParent($parent) {
		if ($parent instanceof Node) {
			$this->parent = $parent;
		} else {
			throw new InvalidArgumentException("The parent must be a Node!");
		}
	}

	/**
	 * Recursive descend into tree and find all nodes with given name.
	 * @param string $name
	 * @param Node $node
	 * @param array $nodeList
	 * @return array List of found nodes.
	 */
	private function _getNodesByNameREC($name, $node, $nodeList = null) {
		for ($node = $node->getFirstChild(); $node != null; $node = $node->getNextSibling()) {
			if ($node->getname() == $name) {
				$nodeList[] = $node;
			}
			if ($node->hasChildren()) {
				$nodeList[] = $this->_getNodesByNameREC($name, $node, $nodeList);
			}
		}
		return $nodeList;
	}
	
}

/*******************************************************************************************
 * TESTS
 */
/*
require_once "TreeCounter.php";
require_once 'TreePrinterWiki.php';

$n1 = new Node("nowiki");
$n2 = new Node("bold");
$n3 = new Node();
$n4 = new Node("bold");

$n1->addChild($n2);
$n1->addChild($n3);
$n3->addChild($n4);

#var_dump($n1);
#var_dump($n1->getLastChild());
#var_dump($n1->getLastChild());
#var_dump($n3->isInside("nowiki"));
#var_dump($n1->getNodesByName("bold"));

$tw = new TreeWalker($n1, new TreePrinterWiki());
echo $tw->getResult();
//*/

?>

