<?hh // strict
namespace PandoDB\MySQL;
// please rename me thx
<<__ConsistentConstruct>>
class IdentifierTreeLeaf implements \PandoDB\IdentifierCollection {
	public function __construct(
		private Set<arraykey> $u_ids,
		public ?int $id = null
	) {}
	public function has_intersect(this $incoming): bool {
		foreach($this->u_ids as $u_id)
			if($incoming->u_ids->contains($u_id))
				return true;
		return false;
	}
	public function intersect(this $incoming): this {
		$intersection = Set{};
		foreach($this->u_ids as $u_id)
			if($incoming->u_ids->contains($u_id))
				$intersection->add($u_id);
		return new static($intersection, $this->id);
	}
	public function is_subset(this $incoming): bool {
		foreach($incoming->u_ids as $u_id)
			if(!$this->u_ids->contains($u_id))
				return false;
		return true;
	}
}