<?hh // strict
namespace PandoDB;
use Pando\Util\Collection\KeyedContainerWrapper as KC;
use Pando\Util\Collection\AsyncKeyedContainerWrapper as AsyncKC;
abstract class Database implements Streamlined<IdentifierTree> {
	private HHRx\Stream<IdentifierTree> $local_stream;
	protected ConditionWaitHandle<IdentifierTree> $call_trigger;
	public function __construct(?HHRx\Stream<IdentifierTree> $global_stream) {
		$this->local_stream = HHRx\Stream::merge_all(Vector{
			$global_stream ?? Stream::empty(),
			new Stream(async {
				foreach($this->local_stream->get_producer() await as $_)
					yield await $this->call_trigger; // create a stream of write calls
			})
		});
	}
	public function get_local_stream(): HHRx\Stream<IdentifierTree> {
		return $this->local_stream;
	}
}