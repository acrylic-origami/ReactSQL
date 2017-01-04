<?hh // strict
namespace Shufflr;
interface Parser<+T> {
	public function parse(string $str): T;
}