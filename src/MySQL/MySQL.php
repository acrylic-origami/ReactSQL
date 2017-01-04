<?hh // strict
namespace PandoDB\MySQL;
use Pando\Util\Collection\KeyedContainerWrapper as KC;
use Pando\Util\Collection\AsyncKeyedContainerWrapper as AsyncKC;
final class MySQL extends \PandoDB\Database {
	private Awaitable<AsyncMySQLConnection> $connection;
	private MySQLDeltaTransformer $delta_transformer;
	public function __construct(Credentials $creds, private MySQLParser $parser, ?\HHRx\Stream<MySQLIdentifierTree<Identifier>> $global_stream = null, ?\AsyncMySQLConnectionPool $pool = null) {
		parent::__construct($global_stream);
		
		$this->connection = ($pool ?? new \AsyncMySQLConnectionPool([]))->connect(
			$creds->host,
			$creds->user,
			$creds->pass,
			$creds->db
		);
		$this->streamer = new MySQLStreamer($creds->db, $this->local_stream); // having a separate MySQLStreamer class isn't the most necessary - everything could live in this class - but it separates the functionality pretty nicely in the first stab
		$this->delta_transformer = new MySQLDeltaTransformer($creds->db);
	}
	public async function SELECT(string $query, (function(): Awaitable<void>) $subscription, int $timeout = -1): MySQLResult {
		$dependencies = $this->delta_transformer($this->parser->parse($query));
		// invariant for command name of query to check for 'SELECT'; I feel that the first word is not necessarily the command name (I know such is the case with SQL Server)
		$result = await (await $this->connection)->query('SELECT ' . ltrim($query, 'SELECT'), $timeout);
		return new MySQLResult($result, $this->streamer->filter($dependencies));
	}
	// public function SELECTF()
}