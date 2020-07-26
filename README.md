
# URL Shortener
## Deploy Steps
1. git pull
2. composer install
3. docker build
4. docker run
## API Interface
- ### `POST` /
    以完整網址取得短網址 code

    Form data:

        {
            url: $url
        }
    - string ***`$url`*** : 完整網址
- ### `GET` /{code}
    以短網址 code 取得完整網址

    - string ***`code`*** : 短網址 code
- #### Return Error Code
    <table>
        <thead>
            <tr>
                <th>errorCode</th>
                <th>type</th>
                <th>description</th>
            </tr>
        </thead>
        <tbody>
            <tr valign="top">
                <td>-1</td>
                <td>unknown</td>
                <td>不明狀態</td>
            </tr>
            <tr valign="top">
                <td>0</td>
                <td>success</td>
                <td>執行成功</td>
            </tr>
            <tr valign="top">
                <td>1</td>
                <td>fail</td>
                <td>執行失敗</td>
            </tr>
            <tr valign="top">
                <td>2</td>
                <td>invalid</td>
                <td>檢核失敗</td>
            </tr>
            <tr valign="top">
                <td>999</td>
                <td>exception</td>
                <td>系統錯誤</td>
            </tr>
        </tbody>
    </table>
## Features
### System/Function:
- implement Redis
- implement worker for shortening
- use DB (no-sql is better than RDB) for mapping storage and race condition
- logs
- optimize config loading method
- authorization (for user-customized short code)
### CI/CD:
- docker-compose
- k8s
- drone (or others)
- code sniffer