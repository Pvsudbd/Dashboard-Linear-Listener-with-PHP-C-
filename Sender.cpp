#define _WIN32_WINNT 0x0A00
#include <iostream>
#include <fstream>
#include <string>
#include "Framework\httplib.h"
#include "Framework\json.hpp"
#include <chrono>

using namespace std::chrono;
using namespace httplib;
using namespace std;
using json = nlohmann::json;

json LinearSearchI(const json& data, const string &nama);
json LinearSearchR(const json& data, const string &nama, size_t index = 0);

int main() {
    Server svr;
    string pilihan;
    string filename;

    svr.Get("/items", [](const Request& req, Response& res) {
    string filename;

    if (!req.has_param("size")) {
        res.status = 400;
        res.set_content("Parameter size tidak ada", "text/plain");
        return;
    }

    string size = req.get_param_value("size");

    if (size == "200") {
        filename = "Data\\Data.json";
    } else if (size == "1000") {
        filename = "Data\\DataBig.json";
    } else if (size == "10000") {
        filename = "Data\\dataten.json";
    } else {
        res.status = 400;
        res.set_content("Ukuran data tidak valid", "text/plain");
        return;
    }

    ifstream file(filename);
    if (!file.is_open()) {
        res.status = 500;
        res.set_content("Gagal membuka file JSON", "text/plain");
        return;
    }

    json data;
    file >> data;

    res.set_content(data.dump(), "application/json");
    });


    svr.Post("/Search", [](const Request& req, Response& res) {
    json req_json;
    try {
        req_json = json::parse(req.body);
    } catch (...) {
        res.status = 400;
        res.set_content("JSON tidak valid", "text/plain");
        return;
    }

    string nama = req_json["item"];
    string size = req_json.value("size", "200"); 
    
    string filename;
    if (size == "200") filename = "Data\\Data.json";
    else if (size == "1000") filename = "Data\\DataBig.json";
    else if (size == "10000") filename = "Data\\dataten.json";
    else {
        res.status = 400;
        res.set_content("Ukuran data tidak valid", "text/plain");
        return;
    }

    ifstream fileSearch(filename);
    if (!fileSearch.is_open()) {
        res.status = 500;
        res.set_content("Gagal membuka file JSON", "text/plain");
        return;
    }

    json dataSearch;
    fileSearch >> dataSearch;

    auto startIter = high_resolution_clock::now();
    json hasilIteratif = LinearSearchI(dataSearch, nama);
    auto stopIter = high_resolution_clock::now();

    auto startRekur = high_resolution_clock::now();
    json hasilRekursif = LinearSearchR(dataSearch, nama);
    auto stopRekur = high_resolution_clock::now();

    json response;
    response["input"] = nama;
    response["iterative"]["time_us"] =
        duration_cast<microseconds>(stopIter - startIter).count();
    response["iterative"]["result"] =
        hasilIteratif.empty() ? json(nullptr) : hasilIteratif;

    response["recursive"]["time_us"] =
        duration_cast<microseconds>(stopRekur - startRekur).count();
    response["recursive"]["result"] =
        hasilRekursif.empty() ? json(nullptr) : hasilRekursif;

    res.set_content(response.dump(), "application/json");
});


    cout << "API jalan di http://127.0.0.1:8080/items\n";
    svr.listen("0.0.0.0", 8080);
}


json LinearSearchI(const json& data,const string &nama) {
    for (auto& DataJ : data) {
        if (DataJ["Item"] == nama)
        {
            return DataJ;
        }
    }
    return json();
}

json LinearSearchR(const json& data, const string &nama, size_t index) {
    if (index >= data.size()) return json();

    if (data[index].contains("Item") &&
        data[index]["Item"] == nama) {
        return data[index];
    }

    return LinearSearchR(data, nama, index + 1);
}