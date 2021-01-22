// time: 20210115
// URL : http://127.0.0.1:4444/producer
// 功能：接收php服务器发送的请求，并输出send到消息队列中
// 输入：请求中包含：
// 输出：Json格式

package main

import (
	"encoding/json"
	"io"
	"log"
	"net/http"
	//"errors"
	"fmt"
    "github.com/streadway/amqp"
)

//Ret ...
type Ret struct {
	Code int    `json:"code,int"`
	Data string `json:"data"`
}
type Order struct {
    Email string        `json:"Email,string"`
	Order_status string     `json:"Order_status,string"`
}

func send(r *http.Request) {

    // 将订单封装成Json格式
    msqg_order := new(Order)
	msqg_order.Email = r.FormValue("email")
	msqg_order.Order_status = r.FormValue("order_status")
	OrderJSON, _ := json.Marshal(msqg_order)
    
    // 连接Rabbit server
    conn, err := amqp.Dial("amqp://msqg:112233qq@localhost:5672/")             // RabbitMQ 的其中一个用户名和密码
    failOnError(err, "Failed to connect to RabbitMQ")                           // 检查调用的结果
    defer conn.Close()
    
    // 创建channel（which is where most of the API for getting things done resides）
    ch, err := conn.Channel()
    failOnError(err, "Failed to open a channel")
    defer ch.Close()
    
    // 声明一个队列
    q, err := ch.QueueDeclare(
      "hello", // name
      false,   // durable
      false,   // delete when unused
      false,   // exclusive
      false,   // no-wait
      nil,     // arguments
    )
    failOnError(err, "Failed to declare a queue")
    
    // 发送消息到队列中
    err = ch.Publish(
      "",     // exchange
      q.Name, // routing key
      false,  // mandatory
      false,  // immediate
      amqp.Publishing {
        ContentType: "text/plain",
        Body:        []byte(OrderJSON),       // order
      })
    failOnError(err, "Failed to publish a message")
    
}
func failOnError(err error, msg string) {       // 检查每一步amqp调用的结果

  if err != nil {
    log.Fatalf("%s: %s", msg, err)
  }
}

func handleRequest(w http.ResponseWriter, r *http.Request) {
	r.ParseForm() 			//解析参数，默认是不会解析的；解析后可用 r.Form提取参数
    fmt.Println("r.Form=", r.Form)     //Get参数时，输出到服务器端的打印信息是map；  

	ret := new(Ret)
	ret.Code = 200
	ret.Data = "提交成功,正在为您处理，请稍后查询"
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	retJSON, _ := json.Marshal(ret)
	io.WriteString(w, string(retJSON))

	send(r)
}

func main() {
	http.HandleFunc("/producer", handleRequest)  //设置访问的路径
	//err := http.ListenAndServe("127.0.0.1:1111", nil)    //设置监听的端口
	err := http.ListenAndServe(":4444", nil)    //设置监听的端口
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}