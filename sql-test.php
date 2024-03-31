<?php
spl_autoload_extensions(".php");
spl_autoload_register();

$mysqli = new \Database\MySQLWrapper();

$createTableQuery = "
    CREATE TABLE IF NOT EXISTS students (
      id INT PRIMARY KEY AUTO_INCREMENT,
      name VARCHAR(100),
      age INT,
      major VARCHAR(50)
    )
";

$mysqli->query($createTableQuery);

$studentsData = [
    ['Alice', 20, 'Computer Science'],
    ['Bob', 22, 'Mathematics'],
    ['Charlie', 21, 'Physics'],
    ['David', 23, 'Chemistry'],
    ['Eve', 20, 'Biology'],
    ['Frank', 22, 'History'],
    ['Grace', 21, 'English Literature'],
    ['Hannah', 23, 'Art History'],
    ['Isaac', 20, 'Economics'],
    ['Jack', 24, 'Philosophy']
];

// foreach ($studentsData as $student) {
//     $insertQuery = "INSERT INTO students (name, age, major) VALUES ('$student[0]', $student[1], '$student[2]')";
//     $mysqli->query($insertQuery);
// }


$selectQuery = "SELECT * FROM students";
$result = $mysqli->query($selectQuery);

while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Age: " . $row['age'] . ", Major: " . $row['major'] . "\n";
}


$updates = [
    ['Alice', 'Physics'],
    ['Bob', 'Art History'],
    ['Charlie', 'Philosophy'],
    ['David', 'Economics']
];

foreach ($updates as $update) {
    $updateQuery = "UPDATE students SET major='$update[1]' WHERE name='$update[0]'";
    $mysqli->query($updateQuery);
}

// 更新されたレコードを表示します
$selectQuery = "SELECT * FROM students";
$result = $mysqli->query($selectQuery);
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Age: " . $row['age'] . ", Major: " . $row['major'] . "\n";
}


$studentsToDelete = ['Alice', 'Bob', 'Charlie'];

foreach ($studentsToDelete as $studentName) {
    $deleteQuery = "DELETE FROM students WHERE name='$studentName'";
    $mysqli->query($deleteQuery);
}

// 残りのレコードを表示します
$selectQuery = "SELECT * FROM students";
$result = $mysqli->query($selectQuery);
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Age: " . $row['age'] . ", Major: " . $row['major'] . "\n";
}