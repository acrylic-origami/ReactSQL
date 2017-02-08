<?hh // strict
namespace PandoDB\MySQL;
class MySQLResult extends \HHRx\Util\Collection\ArtificialKeyedIterable<int, Map<string, mixed>> implements \HHRx\Streamlined<RWIdentifier> {
	// annoyingly, AsyncMySQLQueryResult::mapRowsTyped gives a Map<string, mixed>, not ImmMap<string, arraykey>. Ah well, I would have to work with invariants either way
	public function __construct(
		Vector<Map<string, mixed>> $resultset,
		private RWStream $local_stream
	) {
		parent::__construct();
	}
	public function getIterator(): KeyedIterator<int, Map<string, mixed>> {
		return $this->resultset->getIterator();
	}
	public function get_local_stream(): RWStream {
		return $this->local_stream;
	}
}