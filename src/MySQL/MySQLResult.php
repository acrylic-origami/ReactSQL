<?hh // strict
namespace PandoDB\MySQL;
class MySQLResult extends \Pando\Util\Collection\WrappedIterable<Map<string, mixed>> implements \HHRx\Streamlined<MySQLIdentifierTree<Identifier>> {
	// annoyingly, AsyncMySQLQueryResult::mapRowsTyped gives a Map<string, mixed>, not ImmMap<string, arraykey>. Ah well, I would have to work with invariants either way
	public function __construct(
		Iterable<Map<string, mixed>> $resultset,
		private \HHRx\Stream<MySQLIdentifierTree<Identifier>> $local_stream
	) {
		parent::__construct($resultset);
	}
	public function get_local_stream(): \HHRx\Stream<MySQLIdentifierTree<Identifier>> {
		return $this->local_stream;
	}
}