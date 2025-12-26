#include <iostream>
#include <fstream>
#include <string>
#include "Framework/json.hpp"
#include <chrono>
using namespace std;
using namespace std::chrono;

using json = nlohmann::json;

json LinearSearchI(const json& data, const string &nama);

json LinearSearchR(const json& data, const string &nama, size_t index = 0);

int main() {
    ifstream file("Data\\dataten.json");
    ifstream file2("Data\\dataten.json");
    if (!file.is_open()) {
        cout << "Gagal membuka file JSON!" << endl;
        return 1;
    }

    json data, data2;
    file >> data;
    file2 >> data2;
    file.close();
    file2.close();

    cout << "Total data di file Iteratif: " << data.size() << endl;
    cout << "Total data di file Rekursif: " << data2.size() << endl;

    string nama;
    cout << "Barang apa: ";
    getline(cin, nama);

    auto startIter = high_resolution_clock::now();
    json arif = LinearSearchI(data, nama);
    auto stopIter = high_resolution_clock::now();

    auto startrekur = high_resolution_clock::now();
    json bagas = LinearSearchR(data2, nama);
    auto stopRekur = high_resolution_clock::now();

    auto durationIter = duration_cast<microseconds>(stopIter - startIter);
    auto durationRekur = duration_cast<microseconds>(stopRekur - startrekur);
    
    cout << "Versi iteratif\n"<< arif << "\nWaktu Output: " << durationIter.count() << " ms" <<endl; 
    cout << "Versi Rekursif\n"<< bagas << "\nWaktu Output: " << durationRekur.count() << " ms" <<endl; 
    
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

