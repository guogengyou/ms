// time: 20210115
// URL : 
// 功能：从消息队列中取出json格式的order，并实时处理订单
// 输入：消息队列
// 输出：mysql数据库

package main

import (
	"encoding/json"
	//"io"
	"log"
	//"net/http"
	//"errors"
	"fmt" 
	"github.com/jinzhu/gorm"            // 一定要
	//_ "github.com/jinzhu/gorm/dialects/mysql" // 一定要
	_ "github.com/jinzhu/gorm/dialects/mysql" // 一定要
	"github.com/streadway/amqp"
)
type Order struct {
    Email        string     //`json:"email,string"`
	Order_status string     //`json:"order_status,string"`
}
//定义一个struct类型和MYSQL表进行绑定或者叫映射，struct字段和MYSQL表字段一一对应
//这样，Building类型就可以代表web2020数据库中的某个表了
type Ms_order struct {
	//通过后面的标签说明，定义golang字段和mysql表字段的对应关系
	Email        string `gorm:"column:email"`
	Order_status string `gorm:"column:order_status"`
}

func failOnError(err error, msg string) {
  if err != nil {
    log.Fatalf("%s: %s", msg, err)
  }
}

func HandleOrder(OrderJson []byte) {
    // Json Unmarshal：将json字符串解码到相应的数据结构
    fmt.Println("MQ=", OrderJson)     //Get参数时，输出到服务器端的打印信息是map；
    msqg_order := Order{};
    err:=json.Unmarshal(OrderJson, &msqg_order)
    //解析失败会报错，如json字符串格式不对，缺"号，缺}等。
    if err!=nil{
        fmt.Println(err)
    }
    fmt.Println("!!!") 
    fmt.Println(msqg_order)
	//连接数据库
	db, err := gorm.Open("mysql", "msqg_guogengyou:ppPfWMYHXN@tcp(127.0.0.1:3306)/msqg_guogengyou?charset=utf8&parseTime=True&loc=Local")
	
	if err != nil {
		fmt.Println(err)
	}
	fmt.Println("------------连接数据库成功-----------")
    temp_order := Ms_order{Email: msqg_order.Email, Order_status: msqg_order.Order_status}
    db.Create(&temp_order)
    
   // user := User{Name: "Jinzhu", Age: 18, Birthday: time.Now()}
//db.Create(&user)
}

func main() {
    // 从消息队列中取出json格式的order，并调用协程实时处理订单。
    
    conn, err := amqp.Dial("amqp://msqg:112233qq@localhost:5672/") 
    failOnError(err, "Failed to connect to RabbitMQ")
    defer conn.Close()
    
    ch, err := conn.Channel()
    failOnError(err, "Failed to open a channel")
    defer ch.Close()
    
    // 和send一样也要声明队列， 名字要与send发布的队列一致
    // Note that we declare the queue here, as well. Because we might start the consumer before the publisher, we want to make sure the queue exists before we try to consume messages from it.
    q, err := ch.QueueDeclare(          
      "hello", // name
      false,   // durable
      false,   // delete when unused
      false,   // exclusive
      false,   // no-wait
      nil,     // arguments
    )
    failOnError(err, "Failed to declare a queue")
    
    // 通过channela通道读消息
    msgs, err := ch.Consume(        // returned by amqp::Consume
      q.Name, // queue
      "",     // consumer
      true,   // auto-ack
      false,  // exclusive
      false,  // no-local
      false,  // no-wait
      nil,    // args
    )
    failOnError(err, "Failed to register a consumer")
    
    // we will read the messages from a channel (returned by amqp::Consume) in a goroutine.
    forever := make(chan bool)
    
    // 协程，有消息则立即输出
    go func() {                                             
		for d := range msgs {
			HandleOrder(d.Body)
		}
	}()
	
	// 无消息则阻塞，并打印“Waiting”
    log.Printf(" [*Waiting for Orders. To exit press CTRL+C")
	<-forever        

}