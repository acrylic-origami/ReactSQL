<?hh // strict
namespace PandoDB;
use Pando\Util\Collection\KeyedContainerWrapper as KC;
use Pando\Util\Collection\AsyncKeyedContainerWrapper as AsyncKC;
abstract class Database implements \HHRx\Streamlined<IdentifierCollection> {
	private \HHRx\Stream<IdentifierTree> $local_stream;
	protected ConditionWaitHandle<IdentifierCollection> $call_trigger;
	public function __construct(?\HHRx\Stream<IdentifierCollection> $global_stream) {
		$write_call_stream = new \HHRx\KeyedStream(async {
			foreach($this->local_stream->get_producer() await as $_)
				yield await $this->call_trigger;
		});
		if(is_null($global_stream))
			$this->local_stream = $write_call_stream;
		else
			$this->local_stream = \HHRx\KeyedStream::merge_all(Vector{
				$global_stream,
				$write_call_stream
			});
	}
	public function get_local_stream(): \HHRx\Stream<IdentifierTree> {
		return $this->local_stream;
	}
}