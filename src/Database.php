<?hh // strict
namespace PandoDB;
use HHRx\Util\Collection\KeyedContainerWrapper as KC;
use HHRx\Util\Collection\AsyncKeyedContainerWrapper as AsyncKC;
abstract class Database implements \HHRx\Streamlined<IdentifierCollection> {
	private \HHRx\Stream<IdentifierCollection> $local_stream; // a fork of the global write stream at construction time
	protected ConditionWaitHandle<IdentifierCollection> $call_trigger;
	public function __construct(?\HHRx\Stream<IdentifierCollection> $global_stream, private \HHRx\StreamFactory $factory) {
		$write_call_stream = $this->factory->bounded_make(async () ==> {
			while(true)
				yield await $this->call_trigger;
		});
		$this->call_trigger = ConditionWaitHandle::create($this->factory->get_total_awaitable()->getWaitHandle()); // does getWaitHandle freeze the awaitable? I don't think so and hope not!
		if(is_null($global_stream))
			$this->local_stream = $write_call_stream;
		else
			$this->local_stream = \HHRx\KeyedStream::merge_all(Vector{
				$global_stream,
				$write_call_stream
			});
	}
	public function get_local_stream(): \HHRx\Stream<IdentifierCollection> {
		return $this->local_stream;
	}
}