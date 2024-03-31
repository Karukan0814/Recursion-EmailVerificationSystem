document.addEventListener("DOMContentLoaded", function () {
  // フォームを選択します
  const form = document.getElementById("update-part-form");

  form.addEventListener("submit", function (event) {
    // デフォルトのフォーム送信を防ぎます
    event.preventDefault();

    // FormDataオブジェクトを作成し、コンストラクタでフォームを渡してすべての入力値を取得します
    const formData = new FormData(form);

    // フェッチリクエストを送信します
    fetch("/form/update/part", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json()) // Parse the JSON from the response
      .then((data) => {
        // サーバからのレスポンスデータを処理します
        if (data.status === "success") {
          // 成功メッセージを表示するか、リダイレクトするか、コンソールにログを出力するか
          console.log(data.message);
          if (!formData.has("id")) {
            alert("Part created successful!");
            if (data.id === undefined) form.reset();
            else window.location = "/parts?id=" + data.id;
          } else alert("Part created successful!");
        } else if (data.status === "error") {
          // ユーザーにエラーメッセージを表示します
          console.error(data.message);
          alert("Update failed: " + data.message);
        }
      })
      .catch((error) => {
        // ネットワークエラーまたはJSON解析エラー
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
      });
  });
});
