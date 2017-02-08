<?hh // strict
namespace PandoDB\MySQL;
use HHRx\Util\Collection\KeyedContainerWrapper as KC;
use HHRx\Util\Collection\AsyncKeyedContainerWrapper as AsyncKC;
final class MySQL extends \PandoDB\Database {
	private Awaitable<\AsyncMySQLConnection> $connection;
	private MySQLDeltaTransformer $delta_transformer;
	private Vector<Awaitable<\AsyncMysqlQueryResult>> $results_queue;
	private bool $_in_transaction = false;
	public function __construct(Credentials $creds, private MySQLParser $parser, \HHRx\StreamFactory $factory, ?RWStream $global_stream = null, ?\AsyncMysqlConnectionPool $pool = null) {
		parent::__construct($global_stream, $factory); // order of arguments could use some work
		
		$this->connection = ($pool ?? new \AsyncMysqlConnectionPool([]))->connect(
			$creds->host,
			$creds->port ?? 3306,
			$creds->user,
			$creds->pass,
			$creds->db
		);
		$this->streamer = new MySQLStreamer($creds->db, $this->local_stream); // having a separate MySQLStreamer class isn't the most necessary - everything could live in this class - but it separates the functionality pretty nicely in the first stab
		$this->delta_transformer = new MySQLDeltaTransformer($creds->db);
	}
	private function _register_write(): void {
		
	}
	public async function begin(): Awaitable<void> {
		await $this->connection->query('SET @@autocommit = 0;');
	}
	public async function rollback(): Awaitable<void> {
		await $this->connection->query('ROLLBACK;');
		await $this->connection->query('SET @@autocommit = 1;');
	}
	public async function commit(): Awaitable<void> {
		await $this->connection->query('COMMIT;');
		await $this->connection->query('SET @@autocommit = 1;');
	}
	public async function SELECT(string $query, int $timeout = -1): Awaitable<MySQLResult> {
		await HH\Asio\v($this->results_queue); // Ensure queries execute in their original order. This is especially important for transactions.
		
		$query = 'SELECT ' . ltrim($query, 'SELECT');
		$dependencies = $this->delta_transformer($this->parser->parse($query));
		// invariant for command name of query to check for 'SELECT'; I feel that the first word is not necessarily the command name (I know such is the case with SQL Server)
		$awaitable_result = (await $this->connection)->query($query, $timeout);
		$this->results_queue->add($awaitable_result);
		$result = await $awaitable_result;
		return new MySQLResult($result, $this->streamer->filter($dependencies));
	}
	public async function INSERT(string $query, int $timeout = -1): 
	// public function SELECTF()
}