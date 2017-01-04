<?hh // strict
namespace PandoDB\MySQL;
use \HHRx\Stream;
use \HHRx\Streamlined;
class MySQLStreamer implements Streamlined<this::IdentifierCollection> {
	const type IdentifierCollection = \PandoDB\MySQL\MySQLIdentifierCollection<IdentifierTreeLeaf>;
	const type callback = (function(this::IdentifierCollection): Awaitable<void>);
	
	private Stream<this::IdentifierCollection> $local_stream;
	private this::IdentifierCollection $search_tree;
	private Vector<ConditionWaitHandle<this::IdentifierCollection>> $subscribers; // lol memory leak land
	public function __construct(string $default_db, Stream<this::IdentifierCollection> $global_stream) {
		$this->local_stream = $global_stream;
		$this->search_tree = new MySQLIdentifierCollection($default_db);
		
		$this->local_stream->subscribe(async (this::IdentifierCollection $incoming) ==> {
			$matches = new Set($this->search_tree->intersect($incoming)->map((IdentifierTreeLeaf $leaf) ==> $leaf->get_id())); // Set for unique subscribers
			foreach($matches as $match) {
				$this->subscribers[$match]->succeed($incoming);
				$this->subscribers[$match] = ConditionWaitHandle::create($this->local_stream->get_total_awaitable()); // does this race with the async callback?
			}
			// foreach($this->search_tree->search($incoming) as $match) {
			// 	// under what conditions should I `fail` the ConditionWaitHandle?
			// 	if(!$visited->contains($match)) {
			// 		$visited->add($match);
			// 		$this->subscribers[$match]->succeed($incoming);
			// 		$this->subscribers[$match] = ConditionWaitHandle::create($this->local_stream->get_total_awaitable()); // does this race with the async callback?
			// 	}
			// }
		});
	}
	public function get_local_stream(): Stream<this::IdentifierCollection> {
		return $this->local_stream;
	}
	public function filter(this::IdentifierCollection $dependencies): Stream<this::IdentifierCollection> {
		$wait_handle = ConditionWaitHandle::create($this->local_stream->get_total_awaitable()->getWaitHandle());
		$this->subscribers->add($wait_handle);
		$id = $this->subscribers->count();
		
		//TODO:
		$dependencies->iterate((IdentifierTreeLeaf $leaf) ==> $leaf->id = $id);

		return new \HHRx\KeyedStream(async {
			foreach($this->local_stream->get_producer() await as $_)
				yield $id => (await $this->subscribers[$id]);
		});
	}
}