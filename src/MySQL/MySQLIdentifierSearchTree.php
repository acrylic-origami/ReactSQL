<?hh // strict
namespace PandoDB\MySQL;
type Identifier = shape('u_ids' => Set<arraykey>, 'id' => int);
class MySQLIdentifierSearchTree extends MySQLIdentifierTree<Identifier> {
	public function search<T>(MySQLIdentifierTree<T> $tree, Set<arraykey> $u_ids): Vector<int> {
		$ret = Vector{};
		foreach($this->intersect($tree) as $candidate) {
			foreach($u_ids as $id) {
				if($candidate['u_ids']->contains($id))
					$ret->add($candidate['id']);
			}
		}
		return $ret;
	}
}