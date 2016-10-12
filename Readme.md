#ParallelRPC

一个可并发的基于PHP的rpc框架,支持并发请求,所以除了可用于远程调用外,亦可用于一些耗时运算的本地并发,实现类似多线程的效果。

##基本环境
###服务端

文件命名为rpcrouter.php

```php
require_once(/*autoload策略*/);

$router = new \Parpc\RemoteProcedureRouter();
$router->route();
```
可执行php -S 127.0.0.1:9000 rpcrouter.php测试
###客户端

```php
require_once(/*autoload策略*/);

//创建远程环境
$context = new \Parpc\RemoteContext("http://127.0.0.1:9000/");
```

##普通函数

###服务端定义

```php
//测试函数
function hello($who)
{
	return "hello," . $who;
}

```
###客户端调用

```php
//执行远程函数hello
echo $context->hello("world") . PHP_EOL;

```

##面向对象
###服务端定义类型

```php
class Dog{
	private $name;
	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}
}
```

###客户端调用

```php
$dog = $context->createObject("Dog", array("Bingo"));
echo "There was a farmer had a dog, " . $dog->getName() . " was its name oh" . PHP_EOL;
```

可对比本地调用，代码非常接近

```php
$dog = new Dog("Bingo");
echo "There was a farmer had a dog, " . $dog->getName() . " was its name oh" . PHP_EOL;
```
> ***Tips 1:***
>
> 在代码中增加注释可让IDE帮助我们更方便的编写、调试代码,也可消除PHPMD、PHPcs的警告
>
> ```php
> /** @var Dog $dog 我们知道这并没有真的创建一个Dog实例，但并不妨碍我们把它注释为Dog类型 */
> $dog = $context->createObject("Dog", array("Bingo"));
> ```

##异常处理

可在客户端测试如下代码：

```php
try {
	$cat = $context->createObject("Cat", array("kitty"));
	echo "Was there a cat with name " . $cat->getName();
} catch(\Parpc\RemoteException $e) {
	echo "没有喵星人的事儿: " . $e->getMessage() . PHP_EOL);
}
```
> ***Tips:***
>
> 服务端的任何标准异常都会经由RemoteException包装后，传输到客户端。

##并发访问

服务端没有变化，客户端与同步访问略有不同，因是异步处理，所以采用回调的方式处理，涉及到的全部源码如下：

```php
require_once(/*autoload策略*/);

// 并发调用需要一个额外的caller
$caller = new \Parpc\RemoteProcedureCaller();

// 这里与同步调用亦有所不同，这里没有采用new关键字，而是用一个工厂方法来简化创建RemoteContext的复杂度
$context = $caller->createContext("http://127.0.0.1:9000/");

//普通函数
$context->hello()->onSuccess(function($ret){
	echo $ret . PHP_EOL;
})->onComplete("onComplete");

//远程对象
$dog = $context->createObject("Dog", array("bingo"));
$dog->getName()->onSuccess(function($ret){
	echo "There was a farmer had a dog, " . $ret . " was its name oh" . PHP_EOL;
})->onComplete("onComplete");

//异常处理
$cat = $context->createObject("Cat", array("kitty"));
$cat->getName()->onSuccess(function($ret){
	echo "Was there a cat with name " . $ret;
})->onFail(function(\Parpc\RemoteException $e){
	echo "没有喵星人的事儿: " . $e->getMessage() . PHP_EOL);
})->onComplete("onComplete");

//一次性批量提交
$caller->commit();

function onComplete(\Parpc\RemoteProcedure $procedure) {
	echo "无论成败，风雨无阻，执行完成过程" . $procedure;
})

```
> ***Tips 2:***
>
> 虽然链式风格很吸引人，但如前面tips所述，在代码中增加注释可让IDE帮助我们更方便的调试代码
>
> ```php
> /** @var Dog $dog */
> $dog = $context->createObject("Dog", array("bingo"));
> /** @var \Parpc\RemoteProcedure $procedure */
> $procedure = $dog->getName();
> $procedure->onSuccess ...
> ```

> ***Tips 3:***
>
> onSuccess、onFail、onComplete可叠加使用（如调用两次或以上onSuccess），新的句柄会追加在旧的句柄之后，事件发生后会依次执行。

##安全校验

在服务端加入如下代码：

```php
//只允许本地程序调用
$router->addSecurityValidator(new \Parpc\RemoteAddrValidator(array("127.0.0.1")));

```
可实现\Parpc\SecurityValidator接口，根据自己需求自定义安全控制

> ***tips 4:***
>
> 原则上，该框架可以调用远程环境中定义的任意函数或者对象方法，所以完全不加安全控制将会非常危险。
>
> 如只是系统内部的调用，只允许127.0.0.1访问是优先考虑的选择，如外部调用，推荐的做法是只暴露一个或几个有限的类或方法，可通过自定义SecurityValidator实现。
