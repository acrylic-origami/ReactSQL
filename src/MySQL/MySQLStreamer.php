<?hh // strict
namespace PandoDB\MySQL;
use \HHRx\Stream;
use \HHRx\Streamlined;
class MySQLStreamer implements Streamlined<RWIdentifier> {
	// const type callback = (function(RWIdentifier): Awaitable<void>);
	
	private RWStream $local_stream;
	private RWIdentifier $search_tree;
	private Vector<ConditionWaitHandle<RWIdentifier>> $subscribers = Vector{}; // lol memory leak land
	public function __construct(string $default_db, RWStream $global_stream, private \HHRx\StreamFactory $factory) {
		$this->local_stream = $global_stream;
		$this->search_tree = new MySQLIdentifierCollection($default_db);
		
		$this->local_stream->subscribe(async (RWIdentifier $incoming) ==> {
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
	public function get_local_stream(): RWStream {
		return $this->local_stream;
	}
	public function filter(RWIdentifier $dependencies): RWStream {
		$wait_handle = ConditionWaitHandle::create($this->local_stream->get_total_awaitable()->getWaitHandle());
		$this->subscribers->add($wait_handle);
		$id = $this->subscribers->count();
		
		//TODO:
		$dependencies->iterate_leaves((IdentifierTreeLeaf $leaf) ==> $leaf->id = $id);

		return $this->factory->make(async {
			foreach($this->local_stream->get_producer() await as $_)
				yield $id => (await $this->subscribers[$id]);
		});
	}
}