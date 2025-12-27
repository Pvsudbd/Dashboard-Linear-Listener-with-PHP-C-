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

    cout << "Pilih data:\nA. 200\nB. 1000\nC. 10.000\n> ";
    cin >> pilihan;

    if (pilihan == "A" || pilihan == "a") {
        filename = "Data\\Data.json";
    } else if (pilihan == "B" || pilihan == "b") {
        filename = "Data\\DataBig.json";
    } else {
        filename = "Data\\dataten.json";
    }

    svr.Get("/items", [filename](const Request&, Response& res) {
        ifstream file(filename);
        if (!file.is_open()) {
            res.status = 500;
            res.set_content("Gagal membuka file JSON", "text/plain");
            return;
        }

        json data;
        try {
            file >> data;
        } catch (...) {
            res.status = 500;
            res.set_content("Format JSON rusak", "text/plain");
            return;
        }

        res.set_content(data.dump(), "application/json");

    });

    svr.Post("/Search", [filename](const Request& req, Response& res) {
    ifstream fileSearch(filename);
    if (!fileSearch.is_open()) {
        res.status = 500;
        res.set_content("Gagal membuka file JSON", "text/plain");
        return;
    }

    json dataSearch;
    fileSearch >> dataSearch;


    json req_json;
    try {
        req_json = json::parse(req.body);
    } catch (...) {
        res.status = 400;
        res.set_content("JSON tidak valid", "text/plain");
        return;
    }

    string nama = req_json["item"];

    auto startIter = high_resolution_clock::now();
    json hasilIteratif = LinearSearchI(dataSearch, nama);
    auto stopIter = high_resolution_clock::now();

    auto startrekur = high_resolution_clock::now();
    json hasilRekursif = LinearSearchR(dataSearch, nama);
    auto stopRekur = high_resolution_clock::now();

    auto durationIter = duration_cast<microseconds>(stopIter - startIter).count();
    auto durationRekur = duration_cast<microseconds>(stopRekur - startrekur).count();

    json response;
    response["input"] = nama;

    response["iterative"] = json::object();
    response["iterative"]["result"] = hasilIteratif.empty() ? json(nullptr) : hasilIteratif;
    response["iterative"]["time_us"] = durationIter;


    response["recursive"] = json::object();
    response["recursive"]["result"] = hasilRekursif.empty() ? json(nullptr) : hasilRekursif;
    response["recursive"]["time_us"] = durationRekur;

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