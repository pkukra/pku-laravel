import React, { useState, useEffect } from "react";
import { Card } from "antd";

export default function Index({ pasien }) {
    const [hasilRadiologiData, setHasilRadiologiData] = useState([]);
    const [loadingHasilRadiologi, setLoadingHasilRadiologi] = useState(false);

    const fetchHasilRadiologi = async () => {
        setLoadingHasilRadiologi(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_hasil_radiologi", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setHasilRadiologiData(response?.data?.data || null);
                console.log(response?.data?.data);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingHasilRadiologi(false);
            });
    };

    useEffect(() => {
        fetchHasilRadiologi();
    }, []);

    return (
        <>
            <Card title="Hasil Radiologi" loading={loadingHasilRadiologi}>
                <table
                    className="tw-table tw-table-xs"
                    style={{ width: "100%" }}
                >
                    <tbody align="left">
                        {hasilRadiologiData.map((item, index) => (
                            <tr key={index}>
                                <td
                                    style={{
                                        width: "25%",
                                        verticalAlign: "top",
                                    }}
                                >
                                    {item.MRHNO_TRANSAKSI}
                                </td>
                                <td
                                    style={{ verticalAlign: "top" }}
                                    dangerouslySetInnerHTML={{
                                        __html: item.MRHHASIL,
                                    }}
                                ></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </>
    );
}
