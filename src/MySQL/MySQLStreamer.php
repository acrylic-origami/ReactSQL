<?hh // strict
namespace PandoDB\MySQL;
class MySQLStreamer {
	const type callback = (function(MySQLIdentifierTree<Identifier>): Awaitable<void>);
	
	private \HHRx\Stream<MySQLIdentifierTree<Identifier>> $local_stream;
	private MySQLIdentifierSearchTree $search_tree;
	private Vector<ConditionWaitHandle<MySQLIdentifierTree<Identifier>>> $subscribers; // lol memory leak land
	public function __construct(string $default_db, ?\HHRx\Stream<MySQLIdentifierTree<Identifier>> $global_stream = null) {
		$this->local_stream = $global_stream ?? \HHRx\Stream::empty();
		$this->search_tree = new MySQLIdentifierSearchTree($default_db);
		
		$this->local_stream->subscribe(async (MySQLIdentifierTree<Identifier> $incoming) ==> {
			$visited = Set{};
			foreach($this->search_tree->search($incoming) as $match) {
				// under what conditions should I `fail` the ConditionWaitHandle?
				if(!$visited->contains($match)) {
					$visited->add($match);
					$this->subscribers[$match]->succeed($incoming);
					$this->subscribers[$match] = ConditionWaitHandle::create($this->local_stream->get_total_awaitable()); // does this race with the async callback?
				}
			}
		});
	}
	public function filter(MySQLIdentifierTree<Identifier> $identifiers): Stream<MySQLIdentifierTree> {
		$wait_handle = ConditionWaitHandle::create($this->local_stream->get_total_awaitable());
		$this->subscribers->add($wait_handle);
		$id = $this->subscribers->count();
		
		//TODO:
		$identifier->iterate((Identifier $identifier) ==> $identifier['id'] = $id));

		return new Stream(async {
			foreach($this->local_stream->get_producer() await as $v)
				yield await $this->subscribers[$id];
		}
	}
}